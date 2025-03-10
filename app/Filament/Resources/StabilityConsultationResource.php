<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Forms\Form;
use App\Models\CallCenter;
use Filament\Tables\Table;
use App\Models\Estabelecimento;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Infolists\Components\FileEntry;
use Infolists\Components\ListEntry;
use Forms\Components\TextInput\Mask;
use App\Models\StabilityConsultation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Group;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Tables\Actions\DeleteBulkAction;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\StabilityConsultationResource\Pages;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use App\Filament\Resources\StabilityConsultationResource\RelationManagers;
use App\Filament\Resources\StabilityConsultationResource\Pages\EditStabilityConsultation;
use App\Filament\Resources\StabilityConsultationResource\Pages\ViewStabilityConsultation;
use App\Filament\Resources\StabilityConsultationResource\Pages\ListStabilityConsultations;
use App\Filament\Resources\StabilityConsultationResource\Pages\CreateStabilityConsultation;

class StabilityConsultationResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('estabelecimento') // Carrega o relacionamento corretamente
            ->when(!auth()->user()->hasRole('super_admin'), function (Builder $query) {
                $query->where('estabelecimento_id', auth()->user()->estabelecimento_id);
            });
    }


    protected static ?string $model = StabilityConsultation::class;

    protected static ?string $modelLabel = 'Excursão de Temperatura';

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('estabelecimento_id', auth()->user()->estabelecimento_id);
        }

        return $query->count();
    }


    protected static ?string $navigationIcon = 'lucide-thermometer-snowflake';

    public static function getNavigationIcon(): string
    {
        return 'lucide-thermometer-snowflake';
    }
    public static function getNavigationLabel(): string
    {
        return 'Excursão de Temperatura';
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
                    Wizard\Step::make('Identificação')
                        ->schema([
                            Forms\Components\Select::make('institution_name')
                                ->label('Nome da unidade:')
                                ->helperText('Estabelecimento onde ocorreu excursão')
                                ->options(Estabelecimento::all()->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Document::make('cnpj')
                                ->label('CNPJ:')
                                ->rule('cnpj')
                                ->validation(false)  // Remover em produção
                                ->cnpj('99999999/9999-99')
                                ->helperText('Insira o CNPJ no formato: 00000000/0000-00'),
                            // Campo de verificação da excursão de temperatura
                            Forms\Components\DateTimePicker::make('excursion_verification_at')
                                ->label('Data e horário da identificação da excursão de temperatura')
                                ->required()
                                ->seconds(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (callable $set, $state, $get) {
                                    // Atualiza o campo estimado ao modificar `last_verification_at`
                                    if ($state && $get('returned_to_storage_at')) {
                                        $start = now()->parse($state);
                                        $end = now()->parse($get('returned_to_storage_at'));
                                        $difference = $start->lessThanOrEqualTo($end)
                                            ? $start->diffInHours($end)
                                            : 0; // Retorna 0 se a data inicial for posterior
                                        $set('estimated_exposure_time', $difference);
                                    }
                                }),

                            Forms\Components\DateTimePicker::make('last_verification_at')
                                ->label('Data e horário da última aferição da temperatura')
                                ->required()
                                ->seconds(false)
                                ->live(onBlur: true),

                            Forms\Components\DateTimePicker::make('returned_to_storage_at')
                                ->label('Data e horário do retorno a temperatura preconizada')
                                ->helperText('Data e horário em que o item retornou à condição preconizada de armazenamento.')
                                ->required()
                                ->seconds(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (callable $set, $state, $get) {
                                    // Atualiza o campo estimado ao modificar `returned_to_storage_at`
                                    if ($state && $get('excursion_verification_at')) {
                                        $start = now()->parse($get('excursion_verification_at'));
                                        $end = now()->parse($state);
                                        $difference = $start->lessThanOrEqualTo($end)
                                            ? $start->diffInHours($end)
                                            : 0; // Retorna 0 se a data inicial for posterior
                                        $set('estimated_exposure_time', $difference);
                                    }
                                }),

                            Forms\Components\TextInput::make('estimated_exposure_time')
                                ->label('Tempo Estimado de Exposição')
                                ->helperText('Tempo de exposição estimada à temperatura não recomendada em horas.')
                                ->afterStateUpdated(function (callable $set, $state, $get) {
                                    // Atualiza o campo com o valor calculado
                                    if ($get('last_verification_at') && $get('returned_to_storage_at')) {
                                        $start = now()->parse($get('last_verification_at'));
                                        $end = now()->parse($get('returned_to_storage_at'));
                                        $difference = $start->lessThanOrEqualTo($end)
                                            ? $start->diffInHours($end)
                                            : 0; // Retorna 0 se a data inicial for posterior
                                        $set('estimated_exposure_time', $difference);  // Atualiza o valor do campo
                                    }
                                }),

                        ])
                        ->columns(2),

                    Wizard\Step::make('Dados de Exposição')
                        ->schema([
                            Forms\Components\TextInput::make('max_exposed_temperature')
                                ->label('Temperatura Máxima Exposta (°C)')
                                ->helperText('Insira a temperatura máxima exposta registrada.')
                                ->rule('between:-50,100', 'A temperatura deve estar entre -50°C e 100°C.')
                                ->numeric()
                                ->step(0.1)
                                ->required(),

                            Forms\Components\TextInput::make('min_exposed_temperature')
                                ->label('Temperatura Mínima Exposta (°C)')
                                ->helperText('Insira a temperatura mínima exposta registrada.')
                                ->rule('between:-50,100', 'A temperatura deve estar entre -50°C e 100°C.')
                                ->numeric()
                                ->step(0.1)
                                ->required(),

                            Forms\Components\TextInput::make('local_exposure')
                                ->label('Local de Armazenamento')
                                ->required(),

                            FileUpload::make('file_monitor_temp')
                                ->label('Monitoramento de Temperatura')
                                ->columnSpanFull()
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5128)
                                ->downloadable()
                                ->directory('stabilityConsultation_attachments')
                                ->disk('s3')
                                ->visibility('publico'),

                            Repeater::make('medications')
                                ->label('Medicamentos')
                                ->schema([
                                    Select::make('medicament_id')
                                        ->label('Nome do Medicamento')
                                        ->placeholder('Selecione um medicamento')
                                        ->relationship('medicaments', 'name', fn($query) => $query->orderBy('name')) // Ordenação alfabética
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('medicament_unit')
                                        ->label('Apresentação')
                                        ->options([
                                            'AMPOLA' => 'Ampola',
                                            'CÁPSULA' => 'Cápsula',
                                            'COMPRIMIDO' => 'Comprimido',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                    TextInput::make('medicament_lote')
                                        ->label('Lote')
                                        ->placeholder('Informe o lote')
                                        ->required(),

                                    Select::make('manufacturer_id')
                                        ->label('Fabricante')
                                        ->placeholder('Selecione o fabricante')
                                        ->relationship('manufacturer', 'name', fn($query) => $query->orderBy('name')) // Ordenação alfabética
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),

                                    DatePicker::make('medicament_date')
                                        ->label('Data de Validade')
                                        ->required(),

                                    TextInput::make('medicament_quantity')
                                        ->label('Quantidade')
                                        ->numeric()
                                        ->live() // Atualiza o campo em tempo real
                                        ->required(),

                                    Select::make('program_category')
                                        ->label('Programa de Saúde')
                                        ->searchable()
                                        ->options([
                                            'AÇÃO JUDICIAL' => 'Ação Judicial',
                                            'CEAF 1A - MS' => 'CEAF 1A - MS',
                                            'CEAF 1B SESAB' => 'CEAF 1B SESAB',
                                            'ENDEMIAS' => 'Endemias',
                                            'MINISTÉRIO DA SAÚDE/JUDICIALIZAÇÃO' => 'Ministério da Saúde/Judicialização',
                                            'HEPATITES VIRAIS' => 'Hepatites Virais',
                                            'HOSPITALAR' => 'Hospitalar',
                                            'INSULINA DA ATENÇÃO BÁSICA' => 'Insulina da Atenção Básica',
                                            'ONCOLOGIA' => 'Oncologia',
                                            'PROGRAMA DST/AIDS' => 'Programa DST/AIDS',
                                            'PROTOCOLO ESTADUAL PALIVIZUMABE' => 'Protocolo Estadual Palivizumabe',
                                            'TUBERCULOSE' => 'Tuberculose',
                                        ])
                                        ->required()
                                        ->columnSpan(2),
                                    TextInput::make('unit_value')
                                        ->label('Valor Unitário (R$)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->live() // Atualiza o campo em tempo real
                                        ->required(),
                                    TextInput::make('total_value')
                                        ->label('Total (R$)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->disabled()
                                        ->live() // Atualiza automaticamente quando os valores mudam
                                        ->afterStateHydrated(
                                            fn($set, $get) =>
                                            $set('total_value', ($get('medicament_quantity') ?: 0) * ($get('unit_value') ?: 0))
                                        )
                                        ->columnSpan(1),
                                ])
                                ->columns(4) // Melhor distribuição dos campos
                                ->collapsible() // Permite esconder os itens do repeater para melhor UX
                                ->columnSpanFull(),
                        ])
                        ->columns(3),

                    Wizard\Step::make('Pedido/Distribuição')
                        ->schema([
                            Forms\Components\TextInput::make('order_number')
                                ->label('Número do Pedido')
                                ->disabled(fn($get) => $get('boolean_unit'))
                                ->maxLength(15),
                            Forms\Components\TextInput::make('distribution_number')
                                ->label('Número de Distribuição')
                                ->disabled(fn($get) => $get('boolean_unit'))
                                ->maxLength(15),
                            // Toggle que define a resposta do laboratório
                            Toggle::make('boolean_unit')
                                ->label('Excursão proveniente de um pedido:')
                                ->inline(false)
                                ->offColor('success') // Cor quando desativado
                                ->onColor('danger')  // Cor quando ativado
                                ->offIcon('heroicon-m-check')
                                ->onIcon('heroicon-m-x-mark')
                                ->reactive() // Torna o campo reativo
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                    } else {
                                        $set('distribution_number', null); // Reseta o texto
                                        $set('order_number', null); // Reseta o texto
                                        $set('observations', null); // Reseta o texto
                                    }
                                }),
                            Forms\Components\RichEditor::make('observations')
                                ->label('Observações')
                                ->toolbarButtons([])
                                ->columnSpanFull(),
                        ])->columns(3),

                ])->columnSpan('full')->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('protocol_number')
                    ->label('Protocolo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('institution_name')
                    ->label('Nome da Instituição')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('excursion_verification_at')
                    ->label('Data da Excursão')
                    ->dateTime('d/m/Y')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(StabilityConsultation $record) => route('pdf', $record))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),
                    ActivityLogTimelineTableAction::make('Logs')
                        ->color('info')
                        ->timelineIcons([
                            'created' => 'heroicon-m-check-badge',
                            'updated' => 'heroicon-m-pencil-square',
                        ])
                        ->timelineIconColors([
                            'created' => 'info',
                            'updated' => 'warning',
                        ]),
                    DeleteAction::make()
                        ->successNotificationTitle('Deletado com sucesso.'),
                ])->tooltip('Opções'),
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
            // Primeira seção: Detalhes principais
            InfolistSection::make([
                Fieldset::make('Detalhes Gerais')
                    ->schema([
                        TextEntry::make('protocol_number')
                            ->label('Número do Protocolo:')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpan(1)
                            ->copyable()
                            ->copyMessage('Copiado!'),
                        TextEntry::make('estabelecimento.name')
                            ->label('Nome da Instituição:')
                            ->columnSpan(1)
                            ->copyable()
                            ->copyMessage('Copiado!'),
                        TextEntry::make('cnpj')
                            ->label('CNPJ:')
                            ->columnSpan(1)
                            ->copyable()
                            ->copyMessage('Copiado!'),
                        TextEntry::make('excursion_verification_at')
                            ->label('Excursão de Temperatura')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada')
                            ->columnSpan(1),
                        TextEntry::make('last_verification_at')
                            ->label('Última Verificação')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada')
                            ->columnSpan(1),
                        TextEntry::make('returned_to_storage_at')
                            ->label('Retorno ao Armazenamento')
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada')
                            ->columnSpan(1),
                        TextEntry::make('estimated_exposure_time')
                            ->label('Tempo Estimado de Exposição em horas')
                            ->placeholder('Verifique datas nos campos: Excursão de Temperatura e Retorno ao Armazenamento')
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Fieldset::make('Informações de Exposição')
                    ->schema([
                        TextEntry::make('max_exposed_temperature')
                            ->label('Temperatura Máxima Exposta (°C)')
                            ->columnSpan(1),
                        TextEntry::make('min_exposed_temperature')
                            ->label('Temperatura Mínima Exposta (°C)')
                            ->columnSpan(1),
                        TextEntry::make('local_exposure')
                            ->label('Local de Armazenamento')
                            ->columnSpan(1),

                        Infolists\Components\RepeatableEntry::make('medications')
                            ->label('Medicamentos')
                            ->schema([
                                TextEntry::make('medicament_id')
                                    ->label('Nome do Medicamento')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->formatStateUsing(function ($state) {
                                        return \App\Models\Medicament::find($state)?->name ?? 'Medicamento não encontrado';
                                    })
                                    ->columnSpan(2)
                                    ->copyable()
                                    ->copyMessage('Copiado!'),

                                TextEntry::make('manufacturer_id')
                                    ->label('Fabricante')
                                    ->formatStateUsing(function ($state) {
                                        return \App\Models\Manufacturer::find($state)?->name ?? 'Fabricante não encontrado';
                                    })
                                    ->weight('bold')
                                    ->columnSpan(2),
                                TextEntry::make('program_category')
                                    ->label('Programa de Saúde')
                                    ->columnSpan(2),

                                TextEntry::make('medicament_unit')
                                    ->label('Apresentação')
                                    ->badge() // Destaque visual
                                    ->columnSpan(1),

                                TextEntry::make('medicament_lote')
                                    ->label('Lote')
                                    ->columnSpan(1)
                                    ->copyable()
                                    ->copyMessage('Copiado!'),
                                TextEntry::make('medicament_date')
                                    ->label('Data de Validade')
                                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'Não Informada')
                                    ->columnSpan(1),
                                TextEntry::make('medicament_quantity')
                                    ->label('Quantidade')
                                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')) // Formatação de milhar
                                    ->columnSpan(1),
                                TextEntry::make('unit_value')
                                    ->label('Valor Unitário (R$)')
                                    ->formatStateUsing(fn($state) => 'R$ ' . number_format($state, 2, ',', '.')) // Formatação monetária
                                    ->columnSpan(1),
                                TextEntry::make('total_value')
                                    ->label('Total (R$)')
                                    ->formatStateUsing(fn($state) => $state !== null ? 'R$ ' . number_format($state, 2, ',', '.') : 'R$ 0,00')
                                    ->columnSpan(1),

                            ])
                            ->columns(4) // Melhor distribuição visual
                            ->columnSpanFull(),

                    ])
                    ->columns(2),
            ])->columnSpan(2),


            // Segunda seção: Informações adicionais
            InfolistSection::make([
                Group::make([
                    TextEntry::make('created_at')
                        ->label('Criado em:')
                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada'),
                    TextEntry::make('updated_at')
                        ->label('Atualizado em:')
                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Não Informada'),
                ])
                    ->columns(2),
                Group::make([
                    TextEntry::make('creator.name')
                        ->label('Responsável')
                        ->color('primary'),
                    TextEntry::make('creator.cargo.name')
                        ->label('Cargo')
                        ->color('info'),
                ])
                    ->columns(2),

                TextEntry::make('file_monitor_temp')
                    ->label('Monitoramento de Temperatura')
                    ->columnSpanFull()
                    ->placeholder('Sem anexos')
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
            'index' => Pages\ListStabilityConsultations::route('/'),
            'create' => Pages\CreateStabilityConsultation::route('/create'),
            'view' => Pages\ViewStabilityConsultation::route('/{record}'),
            'edit' => Pages\EditStabilityConsultation::route('/{record}/edit'),
        ];
    }
}
