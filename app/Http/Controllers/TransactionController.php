<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\CompteModel;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionCollection;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    protected $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Display a listing of transactions for a specific account.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['compte.client.user']);

        // Filter by account if provided
        if ($request->has('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_debut')) {
            $query->where('date_transaction', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_transaction', '<=', $request->date_fin);
        }

        // Order by date descending
        $query->orderBy('date_transaction', 'desc');

        $transactions = $query->paginate(15);

        return new TransactionCollection($transactions);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(TransactionRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $compte = CompteModel::findOrFail($validated['compte_id']);

            // Validate transaction based on type
            $this->validationService->validateTransaction($compte, $validated);

            // Create transaction
            $transaction = Transaction::create([
                'montant' => $validated['montant'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'compte_id' => $validated['compte_id'],
                'date_transaction' => $validated['date_transaction'] ?? now(),
            ]);

            // Update account balance
            $this->updateAccountBalance($compte, $transaction);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction créée avec succès',
                'data' => new TransactionResource($transaction->load(['compte.client.user']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['compte.client.user']));
    }

    /**
     * Update the specified transaction.
     */
    public function update(TransactionRequest $request, Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Only allow updating description and date for existing transactions
            $transaction->update([
                'description' => $validated['description'] ?? $transaction->description,
                'date_transaction' => $validated['date_transaction'] ?? $transaction->date_transaction,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction mise à jour avec succès',
                'data' => new TransactionResource($transaction->load(['compte.client.user']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction)
    {
        try {
            DB::beginTransaction();

            $compte = $transaction->compte;

            // Reverse the transaction effect on balance
            $this->reverseTransactionBalance($compte, $transaction);

            $transaction->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions for current user's accounts.
     */
    public function getUserTransactions(Request $request)
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé'
            ], 404);
        }

        $query = Transaction::with(['compte'])
            ->whereHas('compte', function($q) use ($client) {
                $q->where('client_id', $client->id);
            });

        // Apply same filters as index
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_debut')) {
            $query->where('date_transaction', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_transaction', '<=', $request->date_fin);
        }

        $query->orderBy('date_transaction', 'desc');

        $transactions = $query->paginate(15);

        return new TransactionCollection($transactions);
    }

    /**
     * Update account balance based on transaction.
     */
    private function updateAccountBalance(CompteModel $compte, Transaction $transaction)
    {
        switch ($transaction->type) {
            case 'depot':
                $compte->increment('solde', $transaction->montant);
                break;
            case 'retrait':
                $compte->decrement('solde', $transaction->montant);
                break;
            case 'transfert':
                // For transfers, balance is updated separately
                break;
        }
    }

    /**
     * Reverse transaction effect on balance.
     */
    private function reverseTransactionBalance(CompteModel $compte, Transaction $transaction)
    {
        switch ($transaction->type) {
            case 'depot':
                $compte->decrement('solde', $transaction->montant);
                break;
            case 'retrait':
                $compte->increment('solde', $transaction->montant);
                break;
            case 'transfert':
                // For transfers, balance is reversed separately
                break;
        }
    }
}
