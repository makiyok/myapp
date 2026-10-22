<?php

namespace App\Livewire\Forms;

use App\Concerns\VisitValidationRules;
use App\Models\Visit;
use Livewire\Form;

class VisitForm extends Form
{
    use VisitValidationRules;

    public string $title = '';

    public string $description = '';

    public string $venue = '';

    public string $starts_at = '';

    public string $ends_at = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return $this->visitRules();
    }

    /**
     * 編集画面の初期値。datetime-local 用の形式（Y-m-d\TH:i）にそろえる。
     */
    public function setVisit(Visit $visit): void
    {
        $this->title = $visit->title;
        $this->description = $visit->description;
        $this->venue = $visit->venue;
        $this->starts_at = $visit->starts_at->format('Y-m-d\TH:i');
        $this->ends_at = $visit->ends_at->format('Y-m-d\TH:i');
    }
}
