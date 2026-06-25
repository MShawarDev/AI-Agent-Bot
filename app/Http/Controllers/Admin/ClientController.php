<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount(['users', 'salesReports'])->latest()->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'slug'                => 'required|string|max:100|unique:clients,slug|alpha_dash',
            'bot_name'            => 'nullable|string|max:100',
            'system_prompt'       => 'nullable|string|max:4000',
            'currency'            => 'nullable|string|max:10',
            'brand_color'         => 'nullable|string|max:20',
            'starter_prompts_raw' => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $data['starter_prompts'] = $this->parseStarterPrompts($data['starter_prompts_raw'] ?? '');
        unset($data['starter_prompts_raw']);

        $client = Client::create($data);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client created.');
    }

    public function show(Client $client)
    {
        $client->loadCount(['users', 'salesReports', 'conversations']);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'slug'                => 'required|string|max:100|alpha_dash|unique:clients,slug,'.$client->id,
            'bot_name'            => 'nullable|string|max:100',
            'system_prompt'       => 'nullable|string|max:4000',
            'currency'            => 'nullable|string|max:10',
            'brand_color'         => 'nullable|string|max:20',
            'starter_prompts_raw' => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $data['starter_prompts'] = $this->parseStarterPrompts($data['starter_prompts_raw'] ?? '');
        unset($data['starter_prompts_raw']);

        $client->update($data);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }

    private function parseStarterPrompts(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $raw)),
            fn ($line) => $line !== '',
        ));
    }
}
