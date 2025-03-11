<div class="flex flex-col min-h-screen p-6 pb-2 mx-auto">
    <!-- Barra de Progresso do Filtro -->
    <div class="h-1 overflow-hidden bg-gray-800 rounded">
        <div class="h-full transition-all duration-300 bg-indigo-500"
            :class="{
                'w-96': @js($filtro) === 'tudo',
                'w-64': @js($filtro) === 'dashboard',
                'w-32': @js($filtro) === 'ferramenta'
            }">
        </div>
    </div>

    <!-- Título e Filtros -->
    <div class="flex flex-col flex-wrap py-6 pb-0 mb-0 sm:flex-row">
        <h1 class="mb-2 text-2xl font-medium text-gray-900 dark:text-white md:w-3/5 sm:w-2/5 title-font sm:mb-0">
            {{-- Explore alguns projetos que já desenvolvi: --}}
            Central de Recursos SAFTEC:
        </h1>
        <div class="pl-0 text-base leading-relaxed md:w-2/5 sm:w-2/5 sm:pl-10 sm:justify-center">
            <div class="flex justify-center gap-4 mb-6 sm:justify-end">
                <button wire:click="redirecionarParaLogin"
                    class="px-4 py-2 font-semibold text-white transition rounded-lg bg-indigo-950">
                    Login
                    </a>
                </button>
                <button wire:click="$set('filtro', 'tudo')"
                    class="px-4 py-2 font-semibold text-white transition rounded-lg"
                    :class="{ 'bg-blue-600': @js($filtro) === 'tudo', 'bg-gray-500': @js($filtro) !== 'tudo' }">
                    Tudo
                </button>

                <button wire:click="$set('filtro', 'dashboard')"
                    class="px-4 py-2 font-semibold text-white transition rounded-lg"
                    :class="{ 'bg-blue-600': @js($filtro) === 'dashboard', 'bg-gray-500': @js($filtro) !== 'dashboard' }">
                    Dashboards
                </button>

                <button wire:click="$set('filtro', 'ferramenta')"
                    class="px-4 py-2 font-semibold text-white transition rounded-lg"
                    :class="{ 'bg-blue-600': @js($filtro) === 'ferramenta', 'bg-gray-500': @js($filtro) !== 'ferramenta' }">
                    Ferramentas
                </button>
            </div>
        </div>
    </div>

    <!-- Placeholder Skeleton enquanto carrega -->
    <div wire:loading class="w-full">
        <div class="grid w-full grid-cols-1 gap-6 p-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @for ($i = 0; $i < 10; $i++)
                <div
                    class="animate-pulse overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800 shadow-lg transition-transform transform hover:scale-105
                    w-[240px] h-[270px] flex flex-col relative">
                    <div class="w-full h-32 bg-gray-400 dark:bg-gray-600"></div>
                    <div class="flex flex-col flex-grow p-4">
                        <div class="w-3/4 h-4 mb-2 bg-gray-400 dark:bg-gray-600"></div>
                        <div class="w-5/6 h-3 mb-2 bg-gray-400 dark:bg-gray-600"></div>
                        <div class="w-4/6 h-3 bg-gray-400 dark:bg-gray-600"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- Listagem dos Cards -->
    <div wire:loading.remove class="w-full">
        @if ($cards->isEmpty())
            <div class="flex justify-center items-center min-h-[260px] w-full">
                <h1 class="text-xl font-semibold text-center text-gray-900 dark:text-white">
                    Nenhum dashboard disponível.
                </h1>
            </div>
        @else
            <div
                class="grid w-full grid-cols-1 gap-6 p-6 pt-3 pb-0 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($cards as $card)
                    <a href="{{ $card->link }}" target="_blank"
                        class="block overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800 shadow-lg transition-transform transform hover:scale-105
                        w-[240px] h-[270px] flex flex-col relative">

                        <!-- Badge indicando o tipo (Dashboard / Ferramenta) -->
                        <span
                            class="absolute px-3 py-1 text-xs font-semibold text-white uppercase rounded-full top-2 right-2"
                            :class="{
                                'bg-blue-600': '{{ $card->tipo }}'
                                === 'dashboard',
                                'bg-green-500': '{{ $card->tipo }}'
                                === 'ferramenta'
                            }">
                            {{ ucfirst($card->tipo) }}
                        </span>

                        <img src="{{ Storage::disk('s3')->url($card->imagem) }}" alt="{{ $card->nome }}"
                            class="object-cover w-full h-32">

                        <div class="flex flex-col flex-grow p-4">
                            <h2 class="text-base font-semibold tracking-tight text-gray-900 dark:text-white">
                                {{ $card->nome }}
                            </h2>
                            <p class="flex-grow mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $card->descricao }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Paginação Responsiva -->
            <div class="flex justify-center mt-6">
                <div class="inline-flex items-center space-x-2">
                    {{ $cards->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
