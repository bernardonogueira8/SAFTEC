<?php

namespace App\Observers;

use App\Models\Analysis;
use App\Models\StabilityConsultation;

class StabilityConsultationObserver
{
    /**
     * Handle the StabilityConsultation "created" event.
     */
    public function created(StabilityConsultation $stabilityConsultation): void
    {
        $stabilityConsultationId = $stabilityConsultation->id;
        $protocolNumber = $stabilityConsultation->protocol_number;

        $medications = collect($stabilityConsultation->medications)->map(function ($medication) {
            return [
                'medicament_id' => $medication['medicament_id'],
                'medicament_unit' => $medication['medicament_unit'],
                'medicament_lote' => $medication['medicament_lote'],
                'manufacturer_id' => $medication['manufacturer_id'],
                'medicament_date' => $medication['medicament_date'],
                'medicament_quantity' => $medication['medicament_quantity'],
                'program_category' => $medication['program_category'],
                'unit_value' => $medication['unit_value'],
                'total_value' => $medication['total_value'],
            ];
        })->toArray(); // Convertendo para array

        // Criando o registro em AnalysisResource com o campo medications contendo todos os medicamentos
        Analysis::create([
            'stability_consultation_id' => $stabilityConsultationId,
            'protocol_number' => $protocolNumber,
            'medications' => $medications,
        ]);
    }

    /**
     * Handle the StabilityConsultation "updated" event.
     */
    public function updated(StabilityConsultation $stabilityConsultation): void
    {
        //
    }

    /**
     * Handle the StabilityConsultation "deleted" event.
     */
    public function deleted(StabilityConsultation $stabilityConsultation): void
    {
        //
    }

    /**
     * Handle the StabilityConsultation "restored" event.
     */
    public function restored(StabilityConsultation $stabilityConsultation): void
    {
        //
    }

    /**
     * Handle the StabilityConsultation "force deleted" event.
     */
    public function forceDeleted(StabilityConsultation $stabilityConsultation): void
    {
        //
    }
}
