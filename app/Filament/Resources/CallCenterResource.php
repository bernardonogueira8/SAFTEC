<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Infolists;
use Filament\Forms\Form;
use App\Models\CallCenter;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Infolists\Components\FileEntry;
use Infolists\Components\ListEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CallCenterResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\CallCenterResource\RelationManagers;
use App\Models\Estabelecimento;
use App\Models\medicament;
use App\Models\Medicament as ModelsMedicament;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;


class CallCenterResource extends Resource
{
    protected static ?string $model = CallCenter::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()->hasRole('super_admin'), function (Builder $query) {
                $query->where('estabelecimento_id', auth()->user()->estabelecimento_id);
            });
    }
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('estabelecimento_id', auth()->user()->estabelecimento_id);
        }

        return $query->count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    public static function getNavigationIcon(): string
    {
        return 'lucide-message-square-text';
    }

    protected static ?string $modelLabel = 'Ouvidoria';
    public static function getNavigationLabel(): string
    {
        return 'Ouvidoria';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Processos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Detalhes Gerais')
                        ->schema([
                            // Protocolo
                            TextInput::make('protocolo')
                                ->label('Protocolo:')
                                ->placeholder('Número do Protocolo')
                                ->required()
                                ->columnSpan(3),

                            // Setor
                            Select::make('setor')
                                ->options([
                                    'DASF' => 'DASF',
                                    'CAJ' => 'CAJ',
                                    'COAFE' => 'COAFE',
                                    'CAFAB' => 'CAFAB',
                                ])
                                ->label('Setor Encarregada pela Resposta:')
                                ->required()
                                ->columnSpan(3),


                            TextInput::make('demandante')
                                ->label('Demandante:')
                                ->placeholder('Usuário que Registrou Queixa na Ouvidoria')
                                ->required()
                                ->disabled(fn($get) => $get('dado_sigiloso')) // Desabilita se 'dado_sigiloso' estiver ativado
                                ->columnSpan(2),

                            Toggle::make('dado_sigiloso')
                                ->label('Dado Sigiloso')
                                ->inline(false)
                                ->offColor('success') // Cor quando desativado
                                ->onColor('danger')  // Cor quando ativado
                                ->offIcon('heroicon-m-lock-open')
                                ->onIcon('heroicon-m-lock-closed')
                                ->reactive() // Torna o campo reativo
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // Quando ativado, define "Dado Sigiloso"
                                        $set('demandante', 'Dado Sigiloso');
                                    } else {
                                        // Quando desativado, define um valor padrão
                                        $set('demandante', null);
                                    }
                                })
                                ->columnSpan(1),

                            // Unidade
                            Select::make('unidade')
                                ->label('Unidade:')
                                ->placeholder('Unidade envolvida na demanda')
                                ->relationship('estabelecimento', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(3),

                            // Responsável da Aquisição
                            TextInput::make('resp_aquisicao')
                                ->label('Responsável da Aquisição:')
                                ->columnSpan(3),

                            // Data da última dispensação
                            DatePicker::make('dispensation_date')
                                ->label('Data da última dispensação:')
                                ->columnSpan(3),
                        ])
                        ->columns(6),


                    Wizard\Step::make('Lista de Medicamentos')
                        ->schema([
                            Repeater::make('medicaments')
                                ->label('Medicamentos')
                                ->schema([
                                    Select::make('medicament_id')
                                        ->label('Adicionar Medicamento:')
                                        ->placeholder('Informe o nome do medicamento')
                                        ->relationship('medicaments', 'name')  // Relaciona o Select com o nome do medicamento
                                        ->searchable()  // Permite busca por nome
                                        ->preload()  // Carrega as opções ao abrir o campo
                                        ->required(),
                                ])
                                ->nullable()  // Permite que o campo seja nulo
                                ->columns(1),  // Define o layout com 1 coluna

                        ]),


                    Wizard\Step::make('Observações')
                        ->schema([
                            Select::make('author_id')
                                ->label('Autor da Resposta:')
                                ->relationship(
                                    'author',
                                    'name',
                                    modifyQueryUsing: fn($query) => $query->where('estabelecimento_id', auth()->user()->estabelecimento_id)
                                )
                                ->preload()
                                ->searchable()
                                ->required(),
                            DatePicker::make('response_date')
                                ->label('Data da resposta:')
                                ->minDate(now()->subYears(150))
                                ->maxDate(now()),

                            RichEditor::make('observation')
                                ->label('Campo de Observação:')
                                ->disableToolbarButtons([
                                    'blockquote',
                                    'strike',
                                ])
                                ->columnSpanFull(),

                        ])->columns(2),
                    Wizard\Step::make('Anexos')
                        ->schema([
                            FileUpload::make('mirror_file')
                                ->label('Anexo do Espelho:')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5128)
                                ->downloadable()
                                ->directory('callcenter_attachments')
                                ->disk('s3'),


                            FileUpload::make('attachments')
                                ->label('Anexos:')
                                ->multiple()
                                ->panelLayout('grid')
                                ->downloadable()
                                ->visibility('publico')
                                ->disk('s3')
                                ->directory('callcenter_attachments'),

                        ]),


                ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('protocolo')
                    ->label('Protocolo')
                    ->searchable(), // Permite a busca por este campo

                TextColumn::make('demandante')
                    ->label('Demandante')
                    ->searchable()
                    ->sortable(), // Permite ordenar por este campo

                TextColumn::make('setor')
                    ->label('Setor')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'caj' => 'warning',
                        'coafe' => 'info',
                        'dasf_diretoria' => 'success',
                        'cafab' => 'danger',
                        default => 'gray', // Define uma cor padrão para valores não mapeados
                    }),

                TextColumn::make('estabelecimento.name')
                    ->label('Unidade')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Responsável pelo Preenchimento')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('setor')
                    ->options([
                        'caj' => 'CAJ',
                        'coafe' => 'COAFE',
                        'dasf_diretoria' => 'Diretoria',
                        'cafab' => 'CAFAB',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                ActivityLogTimelineTableAction::make('Logs')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // Primeira seção: informações principais
            InfolistSection::make([
                Fieldset::make('Dados do Manifestante')
                    ->schema([
                        TextEntry::make('protocolo')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpan(3)
                            ->copyable()
                            ->copyMessage('Copiado!')
                            ->copyMessageDuration(1500),
                        TextEntry::make('setor')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'CAJ' => 'warning',
                                'COAFE' => 'info',
                                'Diretoria' => 'success',
                                'CAFAB' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('demandante')
                            ->columnSpan(2)
                            ->label('Demandante')
                            ->copyable()
                            ->copyMessage('Copiado!')
                            ->copyMessageDuration(1500)
                            ->placeholder('Dado Sigiloso'),

                        TextEntry::make('estabelecimento.name')
                            ->label('Unidade')
                            ->columnSpan(2)
                            ->placeholder('Não Informada'),
                    ])
                    ->columns(4),
                Fieldset::make('Informações Complementares')
                    ->schema([
                        TextEntry::make('resp_aquisicao')
                            ->label('Responsável da Aquisição:')
                            ->placeholder('Não Informada'),
                        TextEntry::make('dispensation_date')
                            ->label('Data de Dispensação')
                            ->placeholder('Não Informada')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada'),

                        RepeatableEntry::make('medicaments')
                            ->label('Medicamentos')
                            ->placeholder('Nenhum medicamento cadastrado')
                            ->schema([
                                TextEntry::make('medicament_id')
                                    ->label('') // Campo para exibir o ID do medicamento
                                    ->formatStateUsing(function ($state, $record) {
                                        // Verifique se o estado é um ID válido
                                        if ($state) {
                                            // Busque o medicamento pelo ID
                                            $medicament = \App\Models\Medicament::find($state);
                                            if ($medicament) {
                                                return $medicament->name; // Retorne o nome do medicamento
                                            }
                                        }
                                        return 'Medicamento não encontrado'; // Caso não encontre o medicamento
                                    })->columnSpanFull(),
                            ])->columnSpanFull(),



                    ])
                    ->columns(2),
                Fieldset::make('Observações')
                    ->schema([
                        TextEntry::make('response_date')
                            ->label(
                                'Data de Resposta'
                            )
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'Não Informada'),
                        TextEntry::make('author.name')
                            ->label('Autor da Resposta')
                            ->color('primary'),
                        TextEntry::make('observation')
                            ->label('Observações')
                            ->columnSpan(2) // Ocupar duas colunas
                            ->placeholder('Sem Observações')
                            ->markdown(),
                    ])
                    ->columns(2),
            ])->columnSpan(2),

            // Segunda seção: informações adicionais e agrupadas
            InfolistSection::make([
                Group::make([
                    TextEntry::make('created_at')
                        ->label('Criado em:')
                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada'),
                    TextEntry::make('updated_at')
                        ->label('Atualizado em:')
                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada'),

                ])->columns(2),
                TextEntry::make('creator.name')
                    ->label('Responsável pelo Preenchimento')
                    ->color('info'),

                TextEntry::make('mirror_file')
                    ->label('Anexo do Espelho:')
                    ->placeholder('Sem anexo do espelho')
                    ->listWithLineBreaks()->bulleted()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'Sem anexo do espelho';
                        }

                        $url = Storage::disk('s3')->url($state);

                        return sprintf(
                            '<span style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600);"
                class="text-xs rounded-md mx-1 font-medium px-2 min-w-[theme(spacing.6)] py-1
                bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10
                dark:text-custom-400 dark:ring-custom-400/30">
                <a href="%s" target="_blank">%s</a>
            </span>',
                            $url,
                            basename($state)
                        );
                    })
                    ->html(),

                TextEntry::make('attachments')
                    ->label('Anexos')
                    ->placeholder('Sem anexos')
                    ->listWithLineBreaks()->bulleted()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'Sem anexos';
                        }

                        $url = Storage::disk('s3')->url($state);

                        return sprintf(
                            '<span style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600);"
                class="text-xs rounded-md mx-1 font-medium px-2 min-w-[theme(spacing.6)] py-1
                bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10
                dark:text-custom-400 dark:ring-custom-400/30">
                <a href="%s" target="_blank">%s</a>
            </span>',
                            $url,
                            basename($state)
                        );
                    })
                    ->html(),
            ])
                ->columnSpan(1),
        ])
            ->columns(3); // Define o layout com três colunas
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallCenters::route('/'),
            'create' => Pages\CreateCallCenter::route('/create'),
            'view' => Pages\ViewCallCenter::route('/{record}'),
            'edit' => Pages\EditCallCenter::route('/{record}/edit'),
        ];
    }
}
