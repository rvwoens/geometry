<?php

namespace Rvwoens\Geometry;

use Exception as Exception;

/**
 * a basic coordinate with latitude and longitude only
 * @author rvw
 * @version 1.0
 * @since 17-04-2020.
 */
class Coord
{
	//                    Mdcm  M=meter d=decimenter c=centimeter m=millimeter level (approx)
	public const EPSILON = 0.00000001;

	public $latitude = 0.0;
	public $longitude = 0.0;

	/**
	 * Coord constructor.
	 * @param $lat - latitude
	 * @param $lon - longitude
	 */
	public function __construct($lat, $lon)
	{
		if (is_string($lat)) {
			$lat = $this->strToFloat($lat);
		}
		if (is_string($lon)) {
			$lon = $this->strToFloat($lon);
		}
		$this->latitude = $lat;
		$this->longitude = $lon;
	}

	/**
	 * Round the coordinate to a number of digits precision
	 * 6 = about 0.1 m
	 * 5 = about 1 m
	 * 4 = about 10 m
	 * 3 = about 100 m
	 * 2 = about 1km
	 * @param int $precision
	 */
	public function round(int $precision = 6): void
	{
		$this->latitude = round($this->latitude, $precision);
		$this->longitude = round($this->longitude, $precision);
	}

	public function clone(): Coord
	{
		return new static($this->latitude, $this->longitude);
	}

	/**
	 * Immutable version of move.
	 * @param float $distance
	 * @param float $bearing
	 * @return Coord
	 */
	public function movedClone(float $distance, float $bearing): Coord
	{
		return $this->clone()->move($distance, $bearing);
	}

	/**
	 * String format lat,lng (6 digits precision = 10 cm approx)
	 * @return string
	 */
	public function toString(): string
	{
		// LAT,LNG
		return sprintf('%.6f,%.6f', $this->latitude, $this->longitude);
	}

	/**
	 * Wkt string format long,lat
	 * @return string
	 */
	public function toWktString(): string
	{
		// LNG LAT
		return sprintf('%.6f %.6f', $this->longitude, $this->latitude);
	}

	/**
	 * true if equal within 1 millimeter (approx)
	 * @param $other
	 * @return bool
	 */
	public function equals($other): bool
	{
		if ($other == null) {
			return false;
		}
		if ($other == $this) {
			return true;
		}
		if ($other instanceof self) {
			return abs($other->latitude - $this->latitude) <= self::EPSILON && abs($other->longitude - $this->longitude) <= self::EPSILON;
		}
		return false;
	}

	/**
	 * distance in meters between 2 coords
	 * @param Coord $other
	 * @return float
	 */
	public function distance(Coord $other): float
	{
		return static::haversine($this->latitude, $this->longitude, $other->latitude, $other->longitude);
	}

	/**
	 * simpler (faster) distance (not in meters)
	 * @param Coord $other
	 * @return float
	 */
	public function simpleDistance(Coord $other): float
	{
		$dx = abs($this->latitude - $other->latitude);
		$dy = abs($this->longitude - $other->longitude);
		return sqrt($dx * $dx + $dy * $dy);
	}

	/**
	 * get bearing of 2 locatons 0 = north 90=east 180=south 270=west
	 * FROM self TOWARDS point
	 * @param Coord $point
	 * @return float
	 */
	public function bearing(Coord $point): float
	{
		$lat1 = deg2rad($this->latitude);
		$lon1 = deg2rad($this->longitude);

		$lat2 = deg2rad($point->latitude);
		$lon2 = deg2rad($point->longitude);

		$dLon = $lon2 - $lon1;

		$y = sin($dLon) * cos($lat2);
		$x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLon);
		$radiansBearing = atan2($y, $x);

		$degreesBearing = rad2deg($radiansBearing);
		// convert -20 to 340
		if ($degreesBearing < 0.0) {
			return $degreesBearing + 360.0;
		}

		return $degreesBearing;
	}

	/**
	 * move over a distance with a bearing (0=north 90=east 180=south 270=west)
	 * @param float $distance
	 * @param float $bearing
	 * @return Coord
	 */
	public function move(float $distance, float $bearing): Coord
	{
		$bearRadians = deg2rad($bearing);
		$distRadians = $distance / (6372797.6); // earth radius in meters

		$lat1 = $this->latitude * M_PI / 180;
		$lon1 = $this->longitude * M_PI / 180;

		$lat2 = asin(sin($lat1) * cos($distRadians) + cos($lat1) * sin($distRadians) * cos($bearRadians));
		$lon2 = $lon1 + atan2(sin($bearRadians) * sin($distRadians) * cos($lat1), cos($distRadians) - sin($lat1) * sin($lat2));

		$this->latitude = rad2deg($lat2);
		$this->longitude = rad2deg($lon2);

		return $this;
	}


	public function isRDcoord()
	{
		// https://www.javawa.nl/coords.html
		// longitude=X=westeast latitude=Y=northsouth
		// kleinste NL waarde voor X = 14312
		// grootste NL waarde voor X = 277863
		// kleinste NL waarde voor Y = 306840
		// grootste NL waarde voor Y = 619487
		// X,Y can be either Lat or Long
		return ($this->longitude > 14000 && $this->longitude < 300000 && $this->latitude > 300000 && $this->latitude < 650000) ||
				($this->latitude > 14000 && $this->latitude < 300000 && $this->longitude > 300000 && $this->longitude < 650000)
		;
	}

	/**
	 * Convert a RD (Rijksdriehoek) coordinate to WGS84
	 * @throws Exception
	 * @return Coord
	 */
	public function makeWGS84fromRD(): Coord
	{
		if (!$this->isRDcoord()) {
			throw new Exception("Coord is not in RD (Rijksdriehoeksmeting) format");
		}
		if ($this->longitude < $this->latitude) {
			$x = $this->longitude;
			$y = $this->latitude;
		} else {
			$x = $this->latitude;
			$y = $this->longitude;
		}
		$x0 = 155E3;
		$y0 = 463E3;
		$lat0 = 52.1551744;
		$lng0 = 5.38720621;
		$latpqK = [];
		for ($i = 1; $i < 12; $i++) {
			$latpqK[$i] = [];
		}
		$latpqK[1] = ["p" => 0, "q" => 1, "K" => 3235.65389];
		$latpqK[2] = ['p' => 2, "q" => 0, "K" => -32.58297];
		$latpqK[3] = ['p' => 0, "q" => 2, "K" => -0.2475];
		$latpqK[4] = ['p' => 2, "q" => 1, "K" => -0.84978];
		$latpqK[5] = ['p' => 0, "q" => 3, "K" => -0.0665];
		$latpqK[6] = ['p' => 2, "q" => 2, "K" => -0.01709];
		$latpqK[7] = ['p' => 1, "q" => 0, "K" => -0.00738];
		$latpqK[8] = ['p' => 4, "q" => 0, "K" => 0.0053];
		$latpqK[9] = ['p' => 2, "q" => 3, "K" => -3.9E-4];
		$latpqK[10] = ['p' => 4, "q" => 1, "K" => 3.3E-4];
		$latpqK[11] = ['p' => 1, "q" => 1, "K" => -1.2E-4];
		$lngpqL = [];
		for ($i = 1; $i < 13; $i++) {
			$lngpqL[$i] = [];
		}
		$lngpqL[1] = ["p" => 1, "q" => 0, "K" => 5260.52916];
		$lngpqL[2] = ["p" => 1, "q" => 1, "K" => 105.94684];
		$lngpqL[3] = ["p" => 1, "q" => 2, "K" => 2.45656];
		$lngpqL[4] = ["p" => 3, "q" => 0, "K" => -0.81885];
		$lngpqL[5] = ["p" => 1, "q" => 3, "K" => 0.05594];
		$lngpqL[6] = ["p" => 3, "q" => 1, "K" => -0.05607];
		$lngpqL[7] = ["p" => 0, "q" => 1, "K" => 0.01199];
		$lngpqL[8] = ["p" => 3, "q" => 2, "K" => -0.00256];
		$lngpqL[9] = ["p" => 1, "q" => 4, "K" => 0.00128];
		$lngpqL[10] = ["p" => 0, "q" => 2, "K" => 2.2E-4];
		$lngpqL[11] = ["p" => 2, "q" => 0, "K" => -2.2E-4];
		$lngpqL[12] = ["p" => 5, "q" => 0, "K" => 2.6E-4];

		$a = 0;
		// longitude=X=westeast latitude=Y=northsouth
		$dX = 1E-5 * ($x - $x0);  // X
		$dY = 1E-5 * ($y - $y0);   // Y
		for ($i = 1; 12 > $i; $i++) {
			$a += $latpqK[$i]['K'] * pow($dX, $latpqK[$i]['p']) * pow($dY, $latpqK[$i]['q']);
		}
		$newlat = $lat0 + $a / 3600;

		$a = 0;
		$dX = 1E-5 * ($x - $x0);
		$dY = 1E-5 * ($y - $y0);
		for ($i = 1; 13 > $i; $i++) {
			$a += $lngpqL[$i]['K'] * pow($dX, $lngpqL[$i]['p']) * pow($dY, $lngpqL[$i]['q']);
		}
		$newlng = $lng0 + $a / 3600;
		return new self($newlat, $newlng);
	}

	/////////////////////////////////////////////////////////////////////////////////////////
	/// STATIC
	/////////////////////////////////////////////////////////////////////////////////////////
	public const R = 6372.8; // In kilometers
	/**
	 * distance between two lat/lng in meters
	 * not needed: Latlng float distanceTo (Location dest)
	 * float[] results = new float[1];
	 * Location.distanceBetween(1, 2, 2 , 2, results);
	 * @param float $lat1
	 * @param float $lon1
	 * @param float $lat2
	 * @param float $lon2
	 * @return float
	 */
	public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
	{
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);

		$a = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return 1000.0 * static::R * $c;
	}


	/////////////////////////////////////////////////////////////////////////////////////////
	/// private parts
	/////////////////////////////////////////////////////////////////////////////////////////
	private function strToFloat($str): ?float
	{
		// allow 1,5 and 1.5
		$val = str_replace(',', '.', (string) $str);
		if (!preg_match('/[0-9.]*/', $val)) {
			return null;
		}
		return floatval($val);
	}


}
