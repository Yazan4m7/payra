<?php
namespace App\Services;
use App\Models\Employee; use App\Models\EmployeeDocument; use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Storage; use Illuminate\Support\Str; use RuntimeException;
class EmployeeDocumentService
{
private const ALLOWED=['application/pdf','image/jpeg','image/png','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
public function store(Employee $employee,UploadedFile $file,string $category,?string $expiresAt,int $userId,?string $notes=null):EmployeeDocument{$mime=(string)$file->getMimeType();if(!in_array($mime,self::ALLOWED,true))throw new RuntimeException('Unsupported employee document type.');if($file->getSize()===false||$file->getSize()>15*1024*1024)throw new RuntimeException('Employee document exceeds the 15 MB limit.');$sha=hash_file('sha256',$file->getRealPath());$tenantId=(string)tenant()->getTenantKey();$path="tenants/{$tenantId}/employees/{$employee->id}/documents/".Str::uuid();$stream=fopen($file->getRealPath(),'rb');try{if(!Storage::disk('local')->put($path,$stream))throw new RuntimeException('Document storage failed.');}finally{if(is_resource($stream))fclose($stream);}try{return EmployeeDocument::create(['employee_id'=>$employee->id,'category'=>$category,'original_name'=>basename($file->getClientOriginalName()),'storage_path'=>$path,'mime_type'=>$mime,'size_bytes'=>$file->getSize(),'sha256'=>$sha,'expires_at'=>$expiresAt?:null,'uploaded_by'=>$userId,'notes'=>$notes]);}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}}
public function delete(EmployeeDocument $document):void{Storage::disk('local')->delete($document->storage_path);$document->delete();}
}
