<?php

use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TournamentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('tournaments', [TournamentController::class, 'index']);

Route::post('tournament-details', [TournamentController::class, 'details']);

Route::post('tournament-details-uuid', [TournamentController::class, 'detailsByUuid']);

Route::post('add-tournament', [TournamentController::class, 'addTournament']);

Route::post('tournamentCategory', [TournamentController::class, 'tournamentCategory']);

Route::post('current-player-auction', [TournamentController::class, 'currentPlayerAuction']);

Route::post('tournament-category-players', [PlayerController::class, 'categoryPlayers']);

Route::post('unsold-players', [PlayerController::class, 'unsoldPlayers']);

Route::post('current-player-auction-bid-amount', [PlayerController::class, 'currentPlayerAuctionBidAmount']);

Route::post('player-auction-history', [PlayerController::class, 'playerAuctionHistory']);

Route::post('player-auction-details', [PlayerController::class, 'playerAuctionDetails']);

Route::post('current-player-team-bid', [PlayerController::class, 'currentPlayerTeamBid']);

Route::post('update-player-category', [PlayerController::class, 'updatePlayerCategory']);

Route::post('update-player-to-bidding-pool', [PlayerController::class, 'updatePlayerToBiddingPool']);

Route::post('check-team-amount', [TournamentController::class, 'checkTeamAmount']);

Route::post('add-bidding-details', [TournamentController::class, 'addBiddingDetails']);

Route::post('delete-bid', [TournamentController::class, 'deleteBid']);

Route::post('delete-tournament', [TournamentController::class, 'deleteTournament']);

Route::post('update-tournament', [TournamentController::class, 'updateTournament']);

Route::post('tournament-team-details', [TournamentController::class, 'getTeamDetails']);

Route::post('update-player-details', [PlayerController::class, 'updatePlayerDetails']);

Route::post('update-team-details', [TournamentController::class, 'updateTeamDetails']);

Route::post('add-team-details', [TournamentController::class, 'addTeamDetails']);

Route::post('add-player', [PlayerController::class, 'addPlayer']);

Route::post('check-player-already-registered', [PlayerController::class, 'checkPlayerAlreadyRegistered']);

Route::post('delete-player', [PlayerController::class, 'deletePlayer']);

Route::post('reset-tournament', [TournamentController::class, 'resetTournament']);