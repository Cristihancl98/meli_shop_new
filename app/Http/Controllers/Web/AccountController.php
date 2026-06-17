<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {}

    public function index(): View
    {
        $accounts = $this->accountRepository->findByUserId(auth()->id());
        $selectedId = session('active_meli_account_id');

        return view('accounts.index', compact('accounts', 'selectedId'));
    }

    public function switch(Request $request): RedirectResponse
    {
        $accountId = (int) $request->input('account_id');
        $account   = $this->accountRepository->findSelectedByUser(auth()->id(), $accountId);

        if ($account) {
            session(['active_meli_account_id' => $account->id]);
        }

        return redirect()->back()->with('success', 'Tienda cambiada: ' . ($account?->nickname ?? 'Desconocida'));
    }

    public function disconnect(int $id): RedirectResponse
    {
        $userId = auth()->id();

        $this->accountRepository->deactivate($id, $userId);

        if (session('active_meli_account_id') === $id) {
            session()->forget('active_meli_account_id');
        }

        return redirect()->route('accounts.index')
            ->with('success', 'Cuenta desconectada correctamente.');
    }
}
