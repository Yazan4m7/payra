<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
public function up(): void {
 Schema::create('users', function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->enum('role',['company_admin','hr','employee'])->default('employee');$t->string('locale',5)->default('ar');$t->boolean('active')->default(true);$t->rememberToken();$t->timestamps();$t->softDeletes();});
 Schema::create('employees', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('name');$t->string('national_id')->unique();$t->string('ssc_number')->nullable()->index();$t->date('ssc_enrollment_date')->nullable();$t->date('hire_date')->index();$t->string('job_title')->nullable();$t->decimal('salary',16,3);$t->string('bank_iban')->nullable();$t->enum('status',['active','on_leave','terminated','inactive'])->default('active')->index();$t->timestamps();$t->softDeletes();});
}
public function down(): void {Schema::dropIfExists('employees');Schema::dropIfExists('users');}
};
