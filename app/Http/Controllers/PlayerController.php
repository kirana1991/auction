<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PlayerAuctionHistory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PlayerController extends Controller
{
    //
    public function categoryPlayers(Request $request)
    {
        $players = Player::with('team')->where('tournament_id', $request->id);

        if($request->category != '')
        {
            $players = $players->where('category', $request->category);
        }

        return $players->whereNull('deleted_at')->get();

    }

    public function unsoldPlayers(Request $request)
    {

        $players = Player::where('tournament_id', $request->id)
                    ->where('sold_YN', 'N')
                    ->where('auctioned_YN', 'Y')
                    ->whereNull('deleted_at')
                    ->orderBy('category', 'ASC')
                    ->get();

        return $players;

    }

    public function currentPlayerAuctionBidAmount(Request $request)
    {

        $playerCurrentBidAmount = PlayerAuctionHistory::with(['team', 'player'])->where('player_id', $request->id)
                    ->whereNull('deleted_at')
                    ->orderBy('amount', 'DESC')
                    ->first();

        return $playerCurrentBidAmount;

    }

    public function playerAuctionHistory(Request $request)
    {
        $playerAuctionHistory = PlayerAuctionHistory::with(['team'])->where('player_id', $request->id)
                    ->whereNull('deleted_at')
                    ->orderBy('amount', 'DESC')
                    ->get();

        return $playerAuctionHistory;

    }

    public function playerAuctionDetails(Request $request)
    {

        $playerAuctionDetails = Player::find($request->id);
        $teams = Team::where('tournament_id', $playerAuctionDetails->tournament_id)->get();

        return response()->json([
            'teams' => $teams,
            'playerInfo' => $playerAuctionDetails
        ]);

    }

    public function currentPlayerTeamBid(Request $request)
    {
        $playerBidInfo = PlayerAuctionHistory::with(['team', 'player'])
                    ->where('player_id', $request->id)
                    ->whereNull('deleted_at')
                    ->orderBy('amount', 'DESC')
                    ->first();

        return $playerBidInfo;
    }

    public function updatePlayerCategory(Request $request)
    {
        $player = Player::whereIn('id', $request->players)
            ->update([
                'category' => $request->category
            ]);

        if($player)
        {
            return response()->json([
                'status' => true,
                'message' => 'Players category updated'
            ]);
        }
    }

    public function updatePlayerToBiddingPool(Request $request)
    {
        $player = Player::whereIn('id', $request->players)
            ->update([
                'auctioned_YN' => 'N'
            ]);

        if($player)
        {
            return response()->json([
                'status' => true,
                'message' => 'Players moved to bidding'
            ]);
        }
    }

    public function deletePlayer(Request $request)
    {
        $player = Player::find($request->playerId);

        if($player->sold_YN == 'Y' && !is_null($player->team_selected))
        {
            $team = Team::find($player->team_id)
            ->decrement('total_players_selected', 1)
            ->increment('remaining_wallet_amount', $player->amount);
        }

        PlayerAuctionHistory::where('player_id', $request->playerId)
                                ->update([
                                    'deleted_at' => now()
                                ]);

        $player->deleted_at = now();
        $player->save();

        return response()->json([
            'status' => true,
            'data' => $player
        ]);

    }

    public function updatePlayerDetails(Request $request)
    {

        $playerAuctionHistory = PlayerAuctionHistory::where('player_id', $request->id)
                                ->whereNull('deleted_at')
                                ->where('sold_YN', 'Y')
                                ->first();

        if($playerAuctionHistory)
        {
            $team = Team::find($playerAuctionHistory->team_id);
            $remainingAmt = $team->remaining_wallet_amount + $playerAuctionHistory->amount;
            $playersSelected = $team->total_players_selected - 1;
            $team->remaining_wallet_amount = $remainingAmt;
            $team->total_players_selected = $playersSelected;
            $team->save();

            PlayerAuctionHistory::where('player_id', $request->id)
                                ->whereNull('deleted_at')
                                ->where('sold_YN', 'Y')
                                ->update([
                                    'sold_YN' => $request->sold_YN == 'Y' ? 'Y' : null,
                                    'amount' => $request->amount,
                                    'team_id' => $request->team_selected
                                ]);

        }

        $player = Player::find($request->id);

        $fileName = '';

        if($request->hasFile('picture'))
        {
            
            $path ='images/';

            Storage::disk('public')->delete($path.$player->getRawOriginal('profile_img'));

            $fileName = time().$request->picture->getClientOriginalName(); 

            !is_dir($path) &&
                mkdir($path, 0777, true);

            Storage::disk('public')->put($path . $fileName, File::get($request->picture));
        }

        $player->name = $request->name;
        $player->address = $request->address;
        $player->phone_no = $request->phone_no;
        $player->team_selected = is_null($request->team_selected) ? null : $request->team_selected;
        $player->amount = is_null($request->amount) ? null : $request->amount;
        $player->current_auction_player = $request->current_auction_player;
        $player->auctioned_YN = $request->auctioned_YN;
        $player->sold_YN = $request->sold_YN;

        if(!empty($fileName))
        {
            $player->profile_img = $fileName;
        }
        
        $player->save();

        if(!empty($request->team_selected))
        {
            $team = Team::find($request->team_selected);
            $walletAmount = $team->remaining_wallet_amount + (!is_null($request->amount) ? $request->amount : 0);
            $totalPlayersSelected = $team->total_players_selected + 1;
            $team->remaining_wallet_amount = $walletAmount;
            $team->total_players_selected = $totalPlayersSelected;
            $team->save();
        }
        

        return response()->json([
            'status' => 'success',
            'message' => 'Player updated successfully'
        ]);

    }

    public function addPlayer(Request $request)
    {
        
        $data = json_decode($request->otherData);
        
        $tournament = Tournament::where('uuid', $data->tournament_id)
                        ->whereNull('deleted_at')
                        ->first();

        if($tournament)
        {
            $checkPlayerExist = Player::where('phone_no', $data->phone_no)
                                ->where('tournament_id', $tournament->id)
                                ->whereNull('deleted_at')
                                ->first();

            if($checkPlayerExist)
            {
                return response()->json([
                    'status' => false,
                    'details' => $checkPlayerExist,
                    'message' => 'Player already exist'
                ]);
            } else {
                $fileName = null;
                if($request->picture)
                {
                    $fileName = time().$request->picture->getClientOriginalName(); 

                    $path ='images/';

                    !is_dir($path) &&
                        mkdir($path, 0777, true);

                    Storage::disk('public')->put($path . $fileName, File::get($request->picture));
                }

                $player = new Player();
                $player->name = Str::ucfirst($data->name);
                $player->phone_no = $data->phone_no;
                $player->address = $data->address ?? null;
                $player->vertical = $data->vertical ?? null;
                $player->batting_style = $data->batting ?? null;
                $player->bowling_style = $data->bowling ?? null;
                $player->all_rounder = $data->category ?? null;
                $player->department = $data->department ?? null;
                $player->auctioned_YN = $data->auctioned_YN ?? 'N';
                $player->sold_YN = $data->sold_YN ?? 'N';
                $player->current_auction_player = $data->current_auction_player ?? 'N';
                $player->team_selected = $data->team_selected ?? null;
                $player->amount = $data->amount ?? null;
                $player->tournament_id = $tournament->id;
                $player->profile_img = $fileName;
                $player->save();

                return response()->json([
                    'status' => true,
                    'details' => $player,
                    'playerId' => $player->id,
                    'message' => 'Player added successfully'
                ]);

            }
        }
        
    }

    public function checkPlayerAlreadyRegistered(Request $request)
    {
        $tournament = Tournament::with(['player' => function($q) use ($request){
            $q->where('phone_no', $request->phone)->whereNull('deleted_at');
        }])->where('uuid', $request->id)->whereNull('deleted_at')->first();

        if(!empty($tournament->player))
        {
            return response()->json([
                'status' => true,
                'details' => $tournament->player,
            ]);
        } else {
            return response()->json([
                'status' => false
            ]);
        }

    }

    



}
