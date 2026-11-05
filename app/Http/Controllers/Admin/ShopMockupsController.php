<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{DisplayMockups, PrintfulException};
use App\Shop\{eBook, eScore};
use App\Support\AdminTableFilters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopMockupsController extends Controller
{
    public function merchandise(Request $request, MerchandiseProduct $merchandise, DisplayMockups $mockups)
    {
        abort_unless(auth('admin')->user()->isManager(), 403);
        return $this->update($request, $merchandise, $mockups, 'merchandise');
    }

    public function ebook(Request $request, eBook $ebook, DisplayMockups $mockups)
    {
        return $this->update($request, $ebook, $mockups, 'ebooks');
    }

    public function escore(Request $request, eScore $escore, DisplayMockups $mockups)
    {
        return $this->update($request, $escore, $mockups, 'escores');
    }

    private function update(Request $request, Model $product, DisplayMockups $mockups, string $category)
    {
        $remove = $request->input('action') === 'remove';
        $data = $request->validateWithBag('displayMockup', [
            'action' => 'required|in:upload,remove',
            'mockup_version' => 'required|string|size:64',
            'revision' => $product instanceof MerchandiseProduct ? 'required|integer|min:1' : 'nullable',
            'display_mockup' => ($remove ? 'prohibited' : 'required').'|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000',
        ]);
        try {
            $mockups->save($product, $remove ? null : $request->file('display_mockup'), $data['mockup_version'], isset($data['revision']) ? (int) $data['revision'] : null);
        } catch (PrintfulException $exception) {
            throw ValidationException::withMessages(['display_mockup' => $exception->getMessage()])->errorBag('displayMockup');
        }
        $parameters = [$product];
        if ($product instanceof MerchandiseProduct) {
            $parameters += AdminTableFilters::query(AdminTableFilters::fromRequest(Request::create('/', 'GET', $request->query()), MerchandiseController::SORTS));
        }
        return redirect()->route('admin.'.$category.'.edit', $parameters)->withFragment('display-mockup');
    }
}
