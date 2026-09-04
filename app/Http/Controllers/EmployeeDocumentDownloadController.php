<?php
namespace App\Http\Controllers;
use App\Models\EmployeeDocument; use Illuminate\Support\Facades\Storage;
class EmployeeDocumentDownloadController extends Controller { public function __invoke(EmployeeDocument $document){$document->loadMissing('employee');$user=request()->user();abort_unless($user->isHr()||$document->employee->user_id===$user->id,403);abort_unless(Storage::disk('local')->exists($document->storage_path),404);return Storage::disk('local')->download($document->storage_path,$document->original_name,['Content-Type'=>$document->mime_type,'X-Content-Type-Options'=>'nosniff','Content-Disposition'=>'attachment']);} }
