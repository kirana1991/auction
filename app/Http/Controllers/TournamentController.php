<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Player;
use App\Models\PlayerAuctionHistory;
use App\Models\Tournament;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class TournamentController extends Controller
{
    //
    public function index()
    {
        return $tournaments = Tournament::active()->get();
    }

    public function details(Request $request)
    {
        return $tournament = Tournament::with(['teams.players','players'])->find($request->id);
    }

    public function detailsByUuid(Request $request)
    {
        return $tournament = Tournament::where('uuid',$request->id)->first();
    }

    public function tournamentCategory(Request $request)
    {
        return $category = Player::where('tournament_id', $request->id)->whereNull('deleted_at')->groupBy('category')->get();
    }

    public function currentPlayerAuction(Request $request)
    {

        
        $tournamentTeams = Tournament::with('teams')->find($request->id);
        
        $player = Player::where('tournament_id', $request->id)
                    ->where('current_auction_player','Y')
                    ->whereNull('deleted_at');
        
        if($request->category !== '')
        {
            $player = $player->where('category', $request->category);
        }

        $player = $player->first();

        if(!$player)
        {
            $players = Player::where('tournament_id', $request->id)
            ->where('auctioned_YN','N')
            ->whereNull('deleted_at');

            if($request->category !== '')
            {
                $players = $players->where('category', $request->category);
            }

            $players = $players->get()->toArray();

            if(count($players))
            {
                $player = $players[array_rand($players)];
                Player::where('id', $player['id'])
                ->update([
                    'current_auction_player' => 'Y'
                ]);
            }
        }

        $data['teams'] = $tournamentTeams;
        $data['profile'] = $player;

        return $data;
                    
    }

    public function checkTeamAmount(Request $request)
    {
        $team = $request->team;

        $tournamentData = Tournament::with(['team' => function($query) use ($team){
            $query->where('id', $team);
        }])->where('id',$request->id)->active()->first();

        $remainingPlayersToBuy = $tournamentData->team_min_players - $tournamentData->team->total_players_selected - 1;

        //dd($remainingPlayersToBuy);

        if($request->amount > $tournamentData->team->remaining_wallet_amount)
        {
            return response()->json([
                'success' => false,
                'message' => 'Bid amount is more than wallet amount'
            ]);
        } else  if((($tournamentData->team->remaining_wallet_amount - $request->amount) / $tournamentData->minimum_bidding_amount) < $remainingPlayersToBuy){
            return response()->json([
              'status'=> false,
              'message'=> 'Amount is not sufficient to buy the remaining players'
            ]);
        } else if($tournamentData->team->total_players_selected >= $tournamentData->team_max_players  ){
            return response()->json([
              'status'=> false,
              'message'=> 'Squad is full'
            ]);
        } else {
            return response()->json([
                'status'=> true
            ]);
        }

    }

    public function addTournament(Request $request)
    {
        
        $request->validate([
            'file' => 'required|mimes:jpeg,bmp,png,gif,svg',
        ]);

        $fileName = time().$request->file->getClientOriginalName().'.'.$request->file->extension(); 

        $request->file->move(public_path('uploads'), $fileName);

        $path = storage_path('images/');

        !is_dir($path) &&
            mkdir($path, 0777, true);

        Storage::disk('public')->put($path . $fileName, File::get($request->file));

        $tournament = new Tournament();
        $tournament->uuid = Str::uuid();
        $tournament->name = $request->otherData->name;
        $tournament->auction_date = $request->otherData->auction_date;
        $tournament->team_max_players = $request->otherData->team_max_players;
        $tournament->team_min_players = $request->otherData->team_min_players;
        $tournament->total_wallet_amount = $request->otherData->total_wallet_amount;
        $tournament->category_YN = $request->otherData->category_YN;
        $tournament->minimum_bidding_amount = $request->otherData->minimum_bidding_amount;
        $tournament->bid_amount_values = $request->otherData->bid_amount_values;
        $tournament->payment_required = $request->otherData->payment_required;
        $tournament->payment_amount = $request->otherData->payment_amount;
        $tournament->logo = $fileName;
        $tournament->save();

        return response()->json($tournament);

    }

    public function deleteBid(Request $request)
    {
        $playerAuctionHistory = PlayerAuctionHistory::find($request->id);

        if($playerAuctionHistory->sold_YN == 'Y')
        {
            $team = Team::find($playerAuctionHistory->team_id)
            ->decrement('total_players_selected', 1)
            ->increment('remaining_wallet_amount', $request->amount);
        }

        $player = Player::where('id', $playerAuctionHistory->player_id)
                ->update([
                    'sold_YN' => 'N',
                    'auctioned_YN' => 'N',
                    'current_auction_player' => 'Y',
                    'amount' => 0,
                    'team_selected' => null,
                ]);
        
        $playerAuctionHistory->deleted_at = now();
        $playerAuctionHistory->save();

        $playerHistory = PlayerAuctionHistory::where('player_id', $request->playerId)
                        ->where('tournament_id', $request->tournamentId)
                        ->whereNull('deleted_at')
                        ->orderBy('id', 'DESC')
                        ->first();

        return response()->json($playerHistory);
        
    }

    public function deleteTournament(Request $request)
    {
        $tournament = Tournament::where('id', $request->id)
                        ->update([
                            'deleted_at' => now()
                        ]);
        
        return response()->json([
            'status'    => true,
            'message'   => 'Tournament deleted.'
        ]);

    }

    public function updateTournament(Request $request)
    {
        $tournament = Tournament::find($request->id);
        $tournament->name = $request->name;
        $tournament->auction_date = $request->auction_date;
        $tournament->team_max_players = $request->team_max_players;
        $tournament->team_min_players = $request->team_min_players;
        $tournament->total_wallet_amount = $request->total_wallet_amount;
        $tournament->category_YN = $request->category_YN;
        $tournament->minimum_bidding_amount = $request->minimum_bidding_amount;
        $tournament->save();

        return response()->json([
            'status'    => true,
            'message'   => 'Tournament updated.'
        ]);

    }

    public function getTeamDetails(Request $request)
    {
        $team = Team::with('players')->find($request->id);
        return response()->json($team);
    }

    public function updateTeamDetails(Request $request)
    {
        
        $team = Team::find($request->id);
        $team->name = $request->otherData->name;
        $team->total_wallet_amount = $request->otherData->total_wallet_amount;
        $team->remaining_wallet_amount = $request->otherData->remaining_wallet_amount;
        $team->total_players_selected = $request->otherData->total_players_selected;
        $team->team_color = $request->otherData->team_color;
        $team->save();

        return response()->json([
            'status'    => true,
            'message'   => 'Updated successfully'
        ]);

    }

    public function addTeamDetails(Request $request)
    {

        $team = new Team();
        $team->name = $request->otherData->name;
        $team->total_wallet_amount = $request->otherData->total_wallet_amount;
        $team->remaining_wallet_amount = $request->otherData->remaining_wallet_amount;
        $team->total_players_selected = $request->otherData->total_players_selected;
        $team->team_color = $request->otherData->team_color;
        $team->save();

        return response()->json([
            'status'    => true,
            'message'   => 'Team added successfully'
        ]);

    }

    public function resetTournament(Request $request)
    {
        $tournament = Tournament::find($request->id);
        if($tournament)
        {
            $team = Team::where('tournament_id',$tournament->id)
                    ->update([
                        'total_wallet_amount' => $tournament->total_wallet_amount,
                        'remaining_wallet_amount' => $tournament->total_wallet_amount,
                        'total_players_selected' => 0
                    ]);

            $player = Player::where('tournament_id',$tournament->id)
                    ->update([
                        'sold_YN' => 'N',
                        'auctioned_YN' => 'N',
                        'current_auction_player' => 'N',
                        'amount' => 0,
                        'team_selected' => null
                    ]);

            $playerAuctionHistory = PlayerAuctionHistory::where('tournament_id',$tournament->id)
                                    ->update([
                                        'deleted_at' => now()
                                    ]);

            return response()->json([
                                'status'    => true,
                                'message'   => 'tournament successfully updated'
                            ]);
        }
    }
    
    public function addBiddingDetails(Request $request)
    {
        
        $playerAuctions = new PlayerAuctionHistory();
        $playerAuctions->tournament_id = $request->tournament_id;
        $playerAuctions->player_id = $request->player_id;
        $playerAuctions->team_id = $request->team_id;
        $playerAuctions->amount = $request->amount;
        $playerAuctions->save();

        return response()->json($playerAuctions);

    }



}
