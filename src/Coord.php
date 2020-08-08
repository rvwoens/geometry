<?php namespace Rvwoens\Geometry;

/**
 *
 * a basic coordinate with latitude and longitude only
 * @author rvw
 * @version 1.0
 * @since 17-04-2020.
 */
class Coord {
	//                    Mdcm  M=meter d=decimenter c=centimeter m=millimeter level (approx)
	const EPSILON = 0.00000001;

	public $latitude  = 0.0;
	public $longitude = 0.0;

	public function __construct($lat, $lon) {
		if (is_string($lat))
			$lat = $this->strToFloat($lat);
		if (is_string($lon))
			$lon = $this->strToFloat($lon);
		$this->latitude = $lat;
		$this->longitude = $lon;
	}

	public static function copyOf($copyOf) {
		return new static($copyOf->latitude, $copyOf->longitude);
	}

	public static function copyMoved(Coord $copyOf, $distance, $bearing) {
		$new = static::copyOf($copyOf);
		$new->move($distance, $bearing);
		return $new;
	}

	public function toString() {
		// LAT,LNG
		return sprintf("%.6f,%.6f", $this->latitude, $this->longitude);
	}

	public function toWktString() {
		// LNG LAT
		return sprintf("%.6f %.6f", $this->longitude, $this->latitude);
	}

	public function equals($other) {
		if ($other == null) return false;
		if ($other == $this) return true;
		if ($other instanceof Coord)
			return abs($other->latitude - $this->latitude) <= self::EPSILON && abs($other->longitude - $this->longitude) <= self::EPSILON;
		return false;
	}

	public function distance(Coord $other) {
		$dist = static::haversine($this->latitude, $this->longitude, $other->latitude, $other->longitude);
		return $dist;
	}

	/**
	 * get bearing of 2 locatons 0 = north 90=east 180=south 270=west
	 * FROM self TOWARDS p
	 * @param p
	 * @return
	 */
	public function bearing(Coord $p) {
		$lat1 = deg2rad($this->latitude);
		$lon1 = deg2rad($this->longitude);

		$lat2 = deg2rad($p->latitude);
		$lon2 = deg2rad($p->longitude);

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
	 * move over a distance with a bearing
	 * @param distance
	 * @param bearing
	 */
	public function move($distance, $bearing) {
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

	private function doubleEquals($a, $b) {
		return $a == $b ? true : abs($a - $b) < 1E-10;
	}

	private function strToFloat($str) {
		// allow 1,5 and 1.5
		$val = str_replace(",", ".", (string)$str);
		if (!preg_match("/[0-9.]*/", $val)) {
			return null;
		}
		return floatval($val);
	}

	/**
	 * distance between two lat/lng in meters
	 * not needed: Latlng float distanceTo (Location dest)
	 * float[] results = new float[1];
	 * Location.distanceBetween(1, 2, 2 , 2, results);
	 * @param lat1
	 * @param lon1
	 * @param lat2
	 * @param lon2
	 * @return
	 */
	const R = 6372.8; // In kilometers

	public static function haversine($lat1, $lon1, $lat2, $lon2) {
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);

		$a = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return 1000.0 * static::R * $c;
	}


}
