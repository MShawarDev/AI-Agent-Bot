<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Client $client)
    {
        $users = $client->users()->latest()->get();

        return view('admin.users.index', compact('client', 'users'));
    }

    public function create(Client $client)
    {
        return view('admin.users.create', compact('client'));
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $client->users()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'] ?? false,
        ]);

        return redirect()->route('admin.clients.show', $client)->with('status', 'User created.');
    }

    public function edit(User $user)
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'clients'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_admin = $data['is_admin'] ?? false;
        $user->client_id = $data['client_id'] ?? null;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $redirectClient = $user->client_id;

        return $redirectClient
            ? redirect()->route('admin.clients.show', $redirectClient)->with('status', 'User updated.')
            : redirect()->route('admin.clients.index')->with('status', 'User updated (no client assigned).');
    }

    public function destroy(User $user)
    {
        $clientId = $user->client_id;
        $user->delete();

        return redirect()->route('admin.clients.show', $clientId)->with('status', 'User deleted.');
    }
}
