<?php

namespace App\PDF;

use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use App\FavoriteFolder;

class PDFGenerator
{
	protected $view = 'pdf.escore.index';
	protected $pieces, $content;

	public function pieces($pieces)
	{
		$this->pieces = $pieces->whereNotNull('score_url');

		return $this;
	}

	public function request($request)
	{
		$this->content = array_merge([
			'title' => $request['title'],
			'subtitle' => $request['subtitle'],
			'comment' => $request['comment']
		], [
			'pieces' => $this->pieces->pluck('medium_name_with_composer')
		]);

		return $this;
	}

	public function generate()
	{
        $pdf = \PDF::loadView($this->view, $this->content)->download()->getOriginalContent();

        \Storage::disk('public')->put('pdf/file.pdf', $pdf);

        $pdfpath = \Storage::disk('public')->path('pdf/file.pdf');

        return $this->merge($pdfpath)->setFileName('escore.pdf');
	}

	public function merge($pdfpath)
	{
        $merger = PDFMerger::init();

        $merger->addPDF($pdfpath, 'all');

        foreach ($this->pieces as $piece) {
        	if ($piece->is_public_domain)
	        	$merger->addPDF($piece->score_full_path, 'all');
        }
        
        $merger->merge();

        \Storage::disk('public')->delete('pdf/file.pdf');

        return $merger;
	}
}