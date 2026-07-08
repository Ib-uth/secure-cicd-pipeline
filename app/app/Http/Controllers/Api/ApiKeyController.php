<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiKey\StoreApiKeyRequest;
use App\Http\Requests\ApiKey\UpdateApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiKeyController extends Controller
{
    /**
     * List the authenticated user's API keys.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $keys = $request->user()->apiKeys()->latest()->get();

        return ApiKeyResource::collection($keys);
    }

    /**
     * Create a new API key. The plaintext value is returned only here.
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $secret = ApiKey::generateSecret();

        $key = new ApiKey([
            'name' => $request->string('name'),
            'scopes' => $request->input('scopes', ['read']),
        ]);
        // Secret material is set explicitly, never mass-assigned from input.
        $key->key_prefix = $secret['prefix'];
        $key->key_hash = $secret['hash'];

        $request->user()->apiKeys()->save($key);

        return response()->json([
            'data' => new ApiKeyResource($key),
            'plaintext_key' => $secret['plaintext'],
            'message' => 'Store this key now. It will not be shown again.',
        ], 201);
    }

    /**
     * Show a single API key owned by the authenticated user.
     */
    public function show(Request $request, ApiKey $apiKey): ApiKeyResource
    {
        $this->authorizeOwnership($request, $apiKey);

        return new ApiKeyResource($apiKey);
    }

    /**
     * Update the name or scopes of an API key.
     */
    public function update(UpdateApiKeyRequest $request, ApiKey $apiKey): ApiKeyResource
    {
        $this->authorizeOwnership($request, $apiKey);

        $apiKey->update($request->validated());

        return new ApiKeyResource($apiKey);
    }

    /**
     * Revoke and delete an API key.
     */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorizeOwnership($request, $apiKey);

        $apiKey->delete();

        return response()->json(['message' => 'API key revoked.']);
    }

    /**
     * Ensure the key belongs to the requesting user; 404 otherwise so we
     * never disclose the existence of another user's keys.
     */
    protected function authorizeOwnership(Request $request, ApiKey $apiKey): void
    {
        abort_if($apiKey->user_id !== $request->user()->id, 404);
    }
}
