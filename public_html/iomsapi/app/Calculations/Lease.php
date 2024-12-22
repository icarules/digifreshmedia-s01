<?php


namespace App\Calculations;


use Illuminate\Http\Request;

class Lease
{
    protected $response = [];

    protected $aanbetaling;

    protected $rentePercentage;

    public function __construct($aanbetaling = 0, $rentePercentage = 8.99)
    {
        $this->aanbetaling = ($aanbetaling === null ? 0 : $aanbetaling);
        $this->rentePercentage = ($rentePercentage === null ? 8.99 : $rentePercentage);
    }

    public function calculate(Request $request)
    {
        $aanschafwaarde = $this->parseValue($request->input('aanschafwaarde'));
        $leasebedrag = $this->parseValue($request->input('leasebedrag'));
        $btw = $request->input('btw');
        $bpm_bedrag = $request->input('bpm_bedrag');
        $aanbetaling = $this->parseValue($request->input('aanbetaling'));
        $looptijd = $request->input('looptijd');
        $slottermijn = $this->parseValue($request->input('slottermijn'));
        $grijskenteken = $request->input('grijskenteken');
        $inruilen = $request->input('inruilen');
        $inruil_prijsind = $this->parseValue($request->input('inruil_prijsind'));
        $inruil_openstaand = $this->parseValue($request->input('inruil_openstaand'));
        $inruil_maandtarief = $this->parseValue($request->input('inruil_maandtarief'));
        $garantie = $this->parseValue($request->input('garantie'));
        $delivery = $this->parseValue($request->input('delivery'));
        $afleverkosten = $this->parseValue($request->input('afleverkosten', 0));
        $datum_deel_1 = $request->input('datum_deel_1');

        // Bereken te financieren bedrag
        $aanbetaling = $aanbetaling ?: $this->aanbetaling;
        if (is_null($aanbetaling)) {
            $aanbetaling = 0;
        }
        $rijklaarkosten = $aanschafwaarde + $afleverkosten;
        $this->response['afleverkosten']= $afleverkosten;
        $this->response['rijklaarkosten']= $rijklaarkosten;

        $dagBpm = $this->calcDagBpm($bpm_bedrag, $datum_deel_1);
        $this->response['dagBpm']= $dagBpm;

        if ($btw == 1) {
            $btw = ($rijklaarkosten - ($grijskenteken ? 0 : $dagBpm)) / 121.0 * 21.0;
            $aanschaf_excl = $rijklaarkosten - $btw;
        } else {
            $aanschaf_excl = $rijklaarkosten;
        }
        $this->response['btw']= $btw;
        $this->response['aanschaf_excl']= $aanschaf_excl;

        if ($leasebedrag) {
            $aanbetaling = $aanschafwaarde - $leasebedrag;
        }
        $this->response['aanbetaling']= $aanbetaling;

        $tenaamstellingskosten = 0;
        $rijklaar_excl = $aanschaf_excl + $tenaamstellingskosten + $garantie;
        $this->response['rijklaar_excl'] = $rijklaar_excl;

        $tefinancieren = $rijklaar_excl - $aanbetaling + $inruil_openstaand + $delivery;
        if ($inruilen == 1) {
            $tefinancieren -= $inruil_prijsind;
        }
        $tefinancieren = round($tefinancieren);
        $this->response['tefinancieren'] = $tefinancieren;

        // Bereken leasetermijn
        $C = $tefinancieren * 1.21;
        $F = $slottermijn;
        $r = $this->rentePercentage / 1200.0;
        $N = $looptijd;
        $leasetermijn = max(0, ($C*$r * pow(1+$r, $N) - $F*$r) / (pow(1+$r, $N) - 1));
        $this->response['slottermijn'] = $slottermijn;
        $this->response['leasetermijn'] = $leasetermijn;

        return $this->response;
    }

    private function calcDagBpm($bpm_bedrag, $datum_deel_1)
    {
        $afschrijvingspercentage = array(
            0 => array(0, 8),
            1 => array(8, 3),
            3 => array(14, 2.5),
            5 => array(19, 2.25),
            9 => array(28, 1.444),
            18 => array(41, 0.917),
            30 => array(52, 0.833),
            42 => array(62, 0.75),
            54 => array(71, 0.416),
            66 => array(76, 0.416),
            78 => array(81, 0.333),
            90 => array(85, 0.333),
            102 => array(89, 0.25),
            114 => array(92, 0.083),
        );

        $datum_deel_1 = date_parse_from_format("d-m-Y", $datum_deel_1);

        // Current month and year if date in datum_deel_1 can't be parsed
        if (!$datum_deel_1["year"] || !$datum_deel_1["month"]) {
            $datum_deel_1 = date_parse_from_format("d-m-Y", "01-" . date("m-Y"));
        }

        $total_months_old = (date("Y") * 12 + date("n")) - ($datum_deel_1["year"] * 12 + $datum_deel_1["month"]);
        $years_old = floor($total_months_old / 12);
//        $months_old = $total_months_old - $years_old * 12;
        $ap_bpm = array(0, 0);
        $extra_months_old = 0;
        foreach ($afschrijvingspercentage as $m => $ap) {
            if ($total_months_old >= $m) {
                $ap_bpm = $ap;
                $extra_months_old = $total_months_old - $m;
            } else
                break;
        }
        $ap_bpm_calc = $ap_bpm[0] + $ap_bpm[1] * $extra_months_old;
        $dagbpm = ($bpm_bedrag) * (1 - ($ap_bpm_calc / 100));
        return round($dagbpm);
    }

    private function parseValue($value)
    {
        $parsedValue = preg_replace("/[^0-9,]/", "", $value);
        $parsedValue = preg_replace("/,/", ".", $parsedValue);

        return floatval($parsedValue);
    }
}
