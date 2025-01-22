<?php


namespace app\Calculations;


use Illuminate\Http\Request;

class Helpers
{
    /**
     * Get the maximale looptijd
     *
     * @param $bouwjaar int
     * @param $maxLooptijd int // maanden
     * @param $minLooptijd int // maanden
     * @param $defaultLooptijd int // maanden
     * @param $maxLeeftijdEindeContract int // jaren
     *
     * @return float|int
     */
    public static function calculateMaxLoopTijd($bouwjaar, $maxLooptijd, $minLooptijd, $defaultLooptijd, $maxLeeftijdEindeContract)
    {
        // huidig jaar
        $currentYear = date('Y');

        // maximaal eindjaar van het contract (indien geen bouwjaar bekend, neem huidig jaar)
        $maxEindeContract = $bouwjaar ? $bouwjaar + $maxLeeftijdEindeContract : $currentYear + $maxLeeftijdEindeContract;

        // bereken maximale looptijd in maanden
        $calculatedMaxLooptijd = ($maxEindeContract - $currentYear) * 12;

        // maximale looptijd nooit meer dan $maxLooptijd maanden
        $calculatedMaxLooptijd = min($calculatedMaxLooptijd, $maxLooptijd);

        // maximale looptijd nooit minder dan $minLooptijd maanden
        $calculatedMaxLooptijd = max($calculatedMaxLooptijd, $minLooptijd);

        // indien er een default looptijd is ingevuld, dan is de berekende looptijd maximaal de default looptijd
        $calculatedMaxLooptijd = $defaultLooptijd ? min($calculatedMaxLooptijd, $defaultLooptijd) : $calculatedMaxLooptijd;

        return $calculatedMaxLooptijd; // in months
    }

}
