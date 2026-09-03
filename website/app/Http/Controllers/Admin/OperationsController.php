<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Models\Slider;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OperationsController extends Controller
{
    private function admin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(string $area): View
    {
        $this->admin();
        $data = match ($area) {
            'packages' => ['title' => 'Packages', 'records' => Package::with('prices')->latest()->get()],
            'payments' => ['title' => 'Payments', 'records' => Payment::with(['company', 'user'])->latest()->get()],
            'sliders' => ['title' => 'Home Sliders', 'records' => Slider::orderBy('sort_order')->get()],
            'integrations' => ['title' => 'AI & API Integrations', 'records' => ApiIntegration::orderBy('name')->get(), 'providers' => config('integrations.providers')],
            'notifications' => ['title' => 'Notification History', 'records' => PlatformNotification::with('user')->latest()->get()],
            'reports' => ['title' => 'Reports & Analytics', 'records' => collect()],
            default => abort(404),
        };
        return view('admin.operations.index', compact('area', 'data'));
    }

    public function create(string $area): View { $this->admin(); abort_unless(in_array($area, ['packages','sliders','integrations']), 404); return view('admin.operations.form', ['area' => $area, 'record' => null, 'providers' => config('integrations.providers')]); }
    public function edit(string $area, int $record): View { $this->admin(); $model = $this->model($area); return view('admin.operations.form', ['area' => $area, 'record' => $model::findOrFail($record), 'providers' => config('integrations.providers')]); }
    public function store(Request $request, string $area): RedirectResponse { $this->admin(); $model = $this->model($area); $model::create($this->data($request, $area)); return redirect()->route('admin.operations.index', $area)->with('success', 'Record created.'); }
    public function update(Request $request, string $area, int $record): RedirectResponse { $this->admin(); $model = $this->model($area); $item = $model::findOrFail($record); $item->update($this->data($request, $area, $item)); return redirect()->route('admin.operations.index', $area)->with('success', 'Record updated.'); }
    public function destroy(string $area, int $record): RedirectResponse { $this->admin(); $model = $this->model($area); $model::findOrFail($record)->delete(); return back()->with('success', 'Record deleted.'); }

    private function model(string $area): string { return match ($area) { 'packages' => Package::class, 'sliders' => Slider::class, 'integrations' => ApiIntegration::class, default => abort(404) }; }

    private function data(Request $request, string $area, $existing = null): array
    {
        if ($area === 'packages') { $data = $request->validate(['name'=>'required|string|max:100','description'=>'nullable|string','validity_days'=>'required|integer|min:1']); return $data + ['slug'=>Str::slug($data['name']), 'is_active'=>$request->boolean('is_active'), 'entitlements'=>['job_posts'=>(int)$request->input('job_posts',0),'candidate_views'=>(int)$request->input('candidate_views',0),'ai_matching'=>$request->boolean('ai_matching')]]; }
        if ($area === 'sliders') { $data=$request->validate(['title'=>'required|string|max:180','subtitle'=>'nullable|string','image'=>'nullable|image|max:4096','cta_text'=>'nullable|string|max:80','cta_url'=>'nullable|string|max:255','sort_order'=>'required|integer|min:0']); if($request->hasFile('image')){$file=$request->file('image');$directory=public_path('uploads/sliders');if(!is_dir($directory))mkdir($directory,0755,true);$name='slider-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();$file->move($directory,$name);$data['image_path']='uploads/sliders/'.$name;} unset($data['image']); return $data + ['web_enabled'=>$request->boolean('web_enabled'),'app_enabled'=>$request->boolean('app_enabled'),'is_active'=>$request->boolean('is_active')]; }
        $data = $request->validate(['name'=>'required|string|max:120','provider'=>'nullable|string|max:120','environment'=>'required|in:sandbox,live','monthly_limit'=>'nullable|integer|min:0','api_key'=>'nullable|string|max:1000','webhook_secret'=>'nullable|string|max:1000','endpoint'=>'nullable|string|max:255','sender'=>'nullable|string|max:255']);
        $payload = ['key'=>Str::slug($data['name'],'_'), 'name'=>$data['name'], 'provider'=>$data['provider'] ?? null, 'environment'=>$data['environment'], 'monthly_limit'=>$data['monthly_limit'] ?? null, 'is_enabled'=>$request->boolean('is_enabled'), 'endpoint'=>$data['endpoint'] ?? null, 'sender'=>$data['sender'] ?? null];
        if (filled($data['api_key'])) $payload['encrypted_secret'] = Crypt::encryptString($data['api_key']);
        if (filled($data['webhook_secret'])) { $payload['webhook_secret_hint'] = substr($data['webhook_secret'], -4); $payload['encrypted_webhook_secret'] = Crypt::encryptString($data['webhook_secret']); }
        return $payload;
    }
}