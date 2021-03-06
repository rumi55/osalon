<?php


namespace App\Imports;


use App\Contact;
use App\SmsGroupe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportSmsGroupe implements ToCollection, WithHeadingRow
{
    private $rows = 0;

    public function __construct()
    {

    }

    public function collection(Collection $rows)
    {
        $data = [];
        $header = $rows->first();
        $canImport = isset($header["telephone"]);
        if(!$canImport)
        {
            return;
        }

        $smsGroupe = SmsGroupe::create([
            "intitule" => request("intitule")
        ]);

        $date = Carbon::now();
        foreach ($rows as $row)
        {
            $data[] = [
                "nom" => $row["nom"] ?? null,
                "telephone" => $row["telephone"] ?? null,
                "sms_groupe_id" => $smsGroupe->id,
                $date,
                $date,
            ];

            $this->rows++;
        }

        $modelInstance = new Contact();
        $columns = [
            "nom",
            "telephone",
            "sms_groupe_id",
            "created_at",
            "updated_at",
        ];

        if(count($data) > 0)
        {
            batch()->insert($modelInstance, $columns, $data);
        }
        else
        {
            $smsGroupe->delete();
        }
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

}
