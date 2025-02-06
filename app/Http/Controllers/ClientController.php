<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function store(ClientRequest $request)
    {
        try {
            $client = $this->clientService->create(
                $request->validated(),
                $request->file('client_logo')
            );

            return response()->json([
                'message' => 'Client created successfully',
                'data' => $client
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ClientRequest $request, $slug)
    {
        try {
            $client = $this->clientService->update(
                $slug,
                $request->validated(),
                $request->file('client_logo')
            );

            return response()->json([
                'message' => 'Client updated successfully',
                'data' => $client
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $this->clientService->delete($slug);

            return response()->json([
                'message' => 'Client deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $client = $this->clientService->find($slug);

            if (!$client) {
                return response()->json([
                    'message' => 'Client not found'
                ], 404);
            }

            return response()->json([
                'data' => $client
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
