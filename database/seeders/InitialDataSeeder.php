<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $r1 = DB::table('routes')->insertGetId(['name'=>'Ruta 1','code'=>'RT1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $r2 = DB::table('routes')->insertGetId(['name'=>'Ruta 2','code'=>'RT2','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);

        $osvaldo = User::firstOrCreate(['email'=>'osvaldo@gacov.com.co'],[
            'name'=>'Osvaldo','password'=>Hash::make('Gacov2026!'),
            'is_active'=>true,'must_change_password'=>true,
            'route_id'=>$r1,'email_verified_at'=>now(),
        ]);
        $osvaldo->assignRole('conductor');

        $andres = User::firstOrCreate(['email'=>'andres@gacov.com.co'],[
            'name'=>'Andres','password'=>Hash::make('Gacov2026!'),
            'is_active'=>true,'must_change_password'=>true,
            'route_id'=>$r2,'email_verified_at'=>now(),
        ]);
        $andres->assignRole('conductor');

        DB::table('routes')->where('id',$r1)->update(['driver_user_id'=>$osvaldo->id]);
        DB::table('routes')->where('id',$r2)->update(['driver_user_id'=>$andres->id]);

        $adminId = User::where('email','admin@gacov.com.co')->value('id');

        DB::table('warehouses')->insert([
            ['name'=>'Bodega Principal','code'=>'BODEGA','type'=>'bodega','responsible_user_id'=>$adminId,'route_id'=>null,'machine_id'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Vehículo Ruta 1 - Osvaldo','code'=>'VH-RT1','type'=>'vehiculo','responsible_user_id'=>$osvaldo->id,'route_id'=>$r1,'machine_id'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Vehículo Ruta 2 - Andres','code'=>'VH-RT2','type'=>'vehiculo','responsible_user_id'=>$andres->id,'route_id'=>$r2,'machine_id'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);

        $machines = [
            ['M006','MQ6','Máquina 006','San Rafael',$r1],['M013','MQ13','Máquina 013','Colegio Ma.',$r1],
            ['M016','MQ16','Máquina 016','Menang',$r1],['M017','MQ17','Máquina 017','Menang',$r1],
            ['M028','MQ28','Máquina 028','Ruta 1',$r1],['M029','MQ29','Máquina 029','María',$r1],
            ['M030','MQ30','Máquina 030','Ruta 1',$r1],['M032','MQ32','Máquina 032','María',$r1],
            ['M033','MQ33','Máquina 033','San Rafael',$r1],['M037','MQ37','Máquina 037','San Rafael',$r1],
            ['M040','MQ40','Máquina 040','San Rafael / Menang',$r1],['M044','MQ44','Máquina 044','Ruta 1',$r1],
            ['M045','MQ45','Máquina 045','Ruta 1',$r1],['M048','MQ48','Máquina 048','Ruta 1',$r1],
            ['M074','MQ74','Máquina 074','Ruta 1',$r1],['M076','MQ76','Máquina 076','Ruta 1',$r1],
            ['M078','MQ78','Máquina 078','Unisabaneta',$r1],['M081','MQ81','Máquina 081','María',$r1],
            ['M083','MQ83','Máquina 083','Unisabaneta',$r1],['M084','MQ84','Máquina 084','Ruta 1',$r1],
            ['M085','MQ85','Máquina 085','Unisabaneta',$r1],['M099','MQ99','Máquina 099','Ruta 1',$r1],
            ['M103','MQ103','Máquina 103','Unisabaneta',$r1],['M104','MQ104','Máquina 104','Unisabaneta',$r1],
            ['M024','MQ24','Máquina 024','Cta. Brigada',$r2],['M026','MQ26','Máquina 026','Ruta 2',$r2],
            ['M039','MQ39','Máquina 039','Cta. Brigada',$r2],['M043','MQ43','Máquina 043','Cta. Brigada',$r2],
            ['M049','MQ49','Máquina 049','Ruta 2',$r2],['M088','MQ88','Máquina 088','Cta. Brigada',$r2],
            ['M097','MQ97','Máquina 097','Ruta 2',$r2],['M101','MQ101','Máquina 101','Ruta 2',$r2],
            ['M102','MQ102','Máquina 102','Ruta 2',$r2],
            ['M052','MQ52','Máquina 052','General',null],['M053','MQ53','Máquina 053','General',null],
            ['M054','MQ54','Máquina 054','General',null],['M055','MQ55','Máquina 055','General',null],
            ['M056','MQ56','Máquina 056','General',null],['M059','MQ59','Máquina 059','General',null],
            ['M060','MQ60','Máquina 060','General',null],['M061','MQ61','Máquina 061','General',null],
            ['M062','MQ62','Máquina 062','General',null],['M063','MQ63','Máquina 063','General',null],
            ['M064','MQ64','Máquina 064','General',null],['M065','MQ65','Máquina 065','General',null],
            ['M066','MQ66','Máquina 066','General',null],['M067','MQ67','Máquina 067','General',null],
            ['M069','MQ69','Máquina 069','Carne de Key',null],['M080','MQ80','Máquina 080','General',null],
        ];

        foreach ($machines as [$code,$wo,$name,$loc,$routeId]) {
            $mId = DB::table('machines')->insertGetId([
                'code'=>$code,'worldoffice_code'=>$wo,'name'=>$name,
                'location'=>$loc,'route_id'=>$routeId,'type'=>'mixta',
                'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('warehouses')->insert([
                'name'=>"Bodega {$name}",'code'=>"WH-{$code}",'type'=>'maquina',
                'responsible_user_id'=>null,'route_id'=>$routeId,'machine_id'=>$mId,
                'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        $this->command->info('Datos iniciales: 2 rutas, '.count($machines).' máquinas.');
    }
}
