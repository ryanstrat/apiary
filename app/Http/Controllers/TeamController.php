<?php

namespace App\Http\Controllers;

use App\Team;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read-teams', ['only' => ['index', 'indexWeb', 'show', 'showMembers']]);
        $this->middleware('permission:create-teams', ['only' => ['store']]);
        $this->middleware('permission:update-teams', ['only' => ['update']]);
        $this->middleware('permission:update-teams|update-teams-membership-own', ['only' => ['updateMembers']]);
        $this->middleware('permission:delete-teams', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (\Auth::user()->can('read-hidden-teams')) {
            $teams = Team::all();
        } else {
            $teams = Team::visible()->get();
        }

        return response()->json(['status' => 'success', 'teams' => $teams]);
    }

    public function indexWeb()
    {
        $teams = Team::visible()->orderBy('name', 'asc')->get();
        $user = auth()->user();
        //Leave this line in here, it provides team data to the view.
        $user_teams = auth()->user()->teams;

        return view('teams.index')->with(['teams' => $teams, 'user' => $user]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|unique:teams',
            'description' => 'string|max:255|nullable',
            'founding_year' => 'numeric|required',
            'attendable' => 'boolean',
            'hidden' => 'boolean',
        ]);

        try {
            $team = Team::create($request->all());
        } catch (QueryException $e) {
            Bugsnag::notifyException($e);
            $errorMessage = $e->errorInfo[2];

            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }

        if (is_numeric($team->id)) {
            return response()->json(['status' => 'success', 'team' => $team], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => 'unknown_error'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $team = Team::where('id', $id)->orWhere('slug', $id);
        if ($request->input('include') == 'members') {
            $team = $team->with('members');
        }
        $team = $team->first();

        if ($team && $team->hidden == true && \Auth::user()->cant('read-hidden-teams')) {
            return response()->json(['status' => 'error', 'message' => 'team_not_found'], 404);
        } elseif ($team) {
            return response()->json(['status' => 'success', 'team' => $team]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'team_not_found'], 404);
        }
    }

    /**
     * Returns a list of all members of the given team.
     *
     * @param $id integer Team ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function showMembers($id)
    {
        $team = Team::where('id', $id)->orWhere('slug', $id)->first();
        $members = $team->members;

        if ($team && $team->hidden == true && \Auth::user()->cant('read-hidden-teams')) {
            return response()->json(['status' => 'error', 'message' => 'team_not_found'], 404);
        }

        return response()->json(['status' => 'success', 'members' => $members]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $team = Team::where('id', $id)->orWhere('slug', $id)->first();
        if (! $team || ($team->hidden == true && \Auth::user()->cant('update-hidden-teams'))) {
            return response()->json(['status' => 'error', 'message' => 'team_not_found'], 404);
        }

        $this->validate($request, [
            'name' => 'string',
            'description' => 'string|max:255|nullable',
            'founding_year' => 'numeric|nullable',
            'attendable' => 'boolean',
            'hidden' => 'boolean',
        ]);

        try {
            $team->update($request->all());
        } catch (QueryException $e) {
            Bugsnag::notifyException($e);
            $errorMessage = $e->errorInfo[2];

            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }

        if (is_numeric($team->id)) {
            return response()->json(['status' => 'success', 'team' => $team], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => 'unknown_error'], 500);
        }
    }

    /**
     * Updates membership of the given team.
     *
     * @param Request $request
     * @param $id integer
     * @return \Illuminate\Http\Response
     */
    public function updateMembers(Request $request, $id)
    {
        $requestingUser = $request->user();

        $this->validate($request, [
            'user_id' => 'required|numeric|exists:users,id',
            'action' => 'required|in:join,leave',
        ]);

        $team = Team::where('id', $id)->orWhere('slug', $id)->first();
        if (! $team || ($team->hidden == true && \Auth::user()->cant('update-hidden-teams'))) {
            return response()->json(['status' => 'error', 'message' => 'team_not_found'], 404);
        }

        //Enforce users only updating themselves (update-teams-membership-own)
        if ($requestingUser->cant('update-teams') && $requestingUser->id != $request->input('user_id')) {
            return response()->json(['status' => 'error',
                'message' => 'Forbidden - You do not have permission to update this User\'s memberships.', ], 403);
        }

        try {
            $user = User::find($request->input('user_id'));
            if ($request->input('action') == 'join') {
                $team->members()->syncWithoutDetaching($user);
            } else {
                $team->members()->detach($user);
            }
        } catch (QueryException $e) {
            Bugsnag::notifyException($e);
            $errorMessage = $e->errorInfo[2];

            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }

        $team = Team::where('id', $id)->orWhere('slug', $id)->first();

        return response()->json(['status' => 'success', 'team' => $team, 'member' => $user->name], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $team = Team::where('id', $id)->orWhere('slug', $id)->first();
        $deleted = $team->delete();
        if ($deleted) {
            return response()->json(['status' => 'success', 'message' => 'team_deleted']);
        } else {
            return response()->json(['status' => 'error',
                'message' => 'team_not_found', ], 422);
        }
    }
}
