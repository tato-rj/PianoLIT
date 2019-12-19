<?php

namespace App;

class StudioPolicy extends PianoLit
{
	protected $filename;
    protected $durations = [30, 45, 60];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function scopeDurations($query)
    {
        return $this->durations;
    }

    public function getDataAttribute($data)
    {
    	return (array) json_decode($data);
    }

    public function get($field)
    {
        $value = array_key_exists($field, $this->data) ? $this->data[$field] : null;

        return $value;
    }

    public function has($field)
    {
        return array_key_exists($field, $this->data) && !in_array($this->data[$field], ['null', null, '0']);
    }

    public function download()
    {
    	return \PDF::loadView('pdf.studio-policy.index', ['policy' => $this])->download($this->filename());
    }

    public function filename()
    {
    	return str_slug($this->user->last_name) . '-studio-policy-' . $this->created_at->format('m-d-Y') . '.pdf';
    }

    public function events()
    {
        $groups = $this->has('group_classes') ? $this->get('group_classes') . ' group ' . str_plural('class', $this->get('group_classes')) : null;
        $recitals = $this->has('recitals') ? $this->get('recitals') . ' studio ' . str_plural('recital', $this->get('recitals')) : null;

        return array_values(array_filter([$groups, $recitals]));
    }

    public function lessonFees()
    {
        $fees = [];

        foreach ($this->durations as $key => $duration) {
            if ($this->get('lesson_fees')[$key])
                $fees[$duration] = [$this->get('lesson_fees')[$key], $this->get('lesson_duration')[$key]];
        }

        return $fees;
    }

    public function acceptCancellations()
    {
        return $this->get('absence_notice') != 'never';
    }

    public function cancellationOffers()
    {
        if ($this->get('makeup_policy') == 'make up')
            return 'make up';

        if ($this->get('makeup_policy') == 'refund')
            return 'refund';

        if ($this->get('makeup_policy') == 'both')
            return 'make up or refund';

        return null;
    }
}
