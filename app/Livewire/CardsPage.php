<?php

namespace App\Livewire;

use App\Models\Card;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class CardsPage extends Component
{
    use WithPagination;

    public string $filtro = 'tudo'; // Filtro inicial

    protected $paginationTheme = 'tailwind'; // Usa Tailwind na paginação

    public $title = 'Projetos';
    #[Layout('layouts.cards')]

    public function updatingFiltro()
    {
        $this->resetPage(); // Reinicia a paginação ao mudar o filtro
    }

    public function redirecionarParaLogin()
    {
        return redirect()->route('filament.admin.auth.login');
    }

    public function render()
    {
        // Aplicando o filtro corretamente
        $cards = Card::query()
            ->when($this->filtro !== 'tudo', fn($query) => $query->where('tipo', $this->filtro))
            ->paginate(10); // Define o número de cards por página

        return view('livewire.cards-page', compact('cards'));
    }
}
