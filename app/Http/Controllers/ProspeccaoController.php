<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProspeccaoRequest;
use App\Http\Requests\UpdateProspeccaoRequest;
use App\Models\Prospeccao;
use App\Services\ProspeccaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProspeccaoController extends Controller
{
    /**
     * @var list<string>
     */
    private const INDEX_FILTER_KEYS = [
        'search',
        'sort_field',
        'sort_direction',
        'per_page',
        'retorno',
        'lead_score_tier',
        'page',
    ];

    public function __construct(
        private ProspeccaoService $prospeccaoService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->boolean('reset_filters')) {
            session()->forget('prospects.index_filters');

            return to_route('prospects.index');
        }

        $filters = $this->resolveIndexFilters($request);

        if ($request->query->count() === 0 && $filters !== []) {
            return to_route('prospects.index', $filters);
        }

        $prospects = $this->prospeccaoService->list($filters);

        return Inertia::render('prospects/index', [
            'prospects' => $prospects,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('prospects/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProspeccaoRequest $request): RedirectResponse
    {
        $this->prospeccaoService->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prospect cadastrado com sucesso!']);

        return $this->redirectToIndex();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prospeccao $prospect): Response
    {
        return Inertia::render('prospects/edit', [
            'prospect' => $prospect->load('timelines'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProspeccaoRequest $request, Prospeccao $prospect): RedirectResponse
    {
        $this->prospeccaoService->update($prospect, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prospect atualizado com sucesso!']);

        return $this->redirectToIndex();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prospeccao $prospect): RedirectResponse
    {
        $this->prospeccaoService->delete($prospect);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prospect excluído com sucesso!']);

        return $this->redirectToIndex();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveIndexFilters(Request $request): array
    {
        $incoming = collect($request->only(self::INDEX_FILTER_KEYS))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($request->query->count() > 0) {
            $stored = session('prospects.index_filters', []);
            $merged = collect(array_merge($stored, $incoming))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->all();

            session(['prospects.index_filters' => $merged]);

            return $merged;
        }

        return session('prospects.index_filters', []);
    }

    private function redirectToIndex(): RedirectResponse
    {
        return to_route(
            'prospects.index',
            session('prospects.index_filters', []),
        );
    }
}
