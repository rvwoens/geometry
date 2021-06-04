<?php namespace Rvwoens\Geometry;

/**
 * a basic coordinate with latitude and longitude only
 * @author rvw
 * @version 1.0
 * @since 17-04-2020.
 */
class Coord {
	//                    Mdcm  M=meter d=decimenter c=centimeter m=millimeter level (approx)
	const EPSILON = 0.00000001;

	public $latitude = 0.0;
	public $longitude = 0.0;

	/**
	 * Coord constructor.
	 * @param $lat - latitude
	 * @param $lon - longitude
	 */
	public function __construct($lat, $lon) {
		if (is_string($lat))
			$lat = $this->strToFloat($lat);
		if (is_string($lon))
			$lon = $this->strToFloat($lon);
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
	public function round(int $precision=6):void {
		$this->latitude=round($this->latitude, $precision);
		$this->longitude=round($this->longitude, $precision);
	}

	public function clone(): Coord {
		return new static($this->latitude, $this->longitude);
	}

	/**
	 * Immutable version of move.
	 * @param float $distance
	 * @param float $bearing
	 * @return Coord
	 */
	public function movedClone(float $distance, float $bearing):Coord {
		return $this->clone()->move($distance, $bearing);
	}

	/**
	 * String format lat,lng (6 digits precision = 10 cm approx)
	 * @return string
	 */
	public function toString(): string {
		// LAT,LNG
		return sprintf('%.6f,%.6f', $this->latitude, $this->longitude);
	}

	/**
	 * Wkt string format long,lat
	 * @return string
	 */
	public function toWktString(): string {
		// LNG LAT
		return sprintf('%.6f %.6f', $this->longitude, $this->latitude);
	}

	/**
	 * true if equal within 1 millimeter (approx)
	 * @param $other
	 * @return bool
	 */
	public function equals($other): bool {
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
	public function distance(Coord $other): float {
		return static::haversine($this->latitude, $this->longitude, $other->latitude, $other->longitude);
	}

	/**
	 * get bearing of 2 locatons 0 = north 90=east 180=south 270=west
	 * FROM self TOWARDS point
	 * @param Coord $point
	 * @return float
	 */
	public function bearing(Coord $point):float {
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
	public function move(float $distance, float $bearing):Coord {
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


	/////////////////////////////////////////////////////////////////////////////////////////
	/// STATIC
	/////////////////////////////////////////////////////////////////////////////////////////
	const R = 6372.8; // In kilometers
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
	public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2):float {
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
	private function strToFloat($str): ?float {
		// allow 1,5 and 1.5
		$val = str_replace(',', '.', (string) $str);
		if (!preg_match('/[0-9.]*/', $val)) {
			return null;
		}
		return floatval($val);
	}
}
