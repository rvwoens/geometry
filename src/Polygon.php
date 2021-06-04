<?php namespace Rvwoens\Geometry;

use Exception;

/**
 * Polygon class. Basically a set of Coords (at least 3, a triangle)
 * Polygons close themselves: The last point is connected to the first.
 * Can be CCW or CW, does not matter. However can not contain holes etc.
 * @author rvw
 * @version 1.0
 * @since 20-04-2020.
 */
class Polygon {
	private $poly = [];   // smallest poly is a triangle!

	/**
	 * Polygon constructor.
	 *
	 * @param $latlngpoly - simple polygon [ [4,5], [4,6] ..], coords polygon [ Coord, Coord ..] or latlng polygon [ ['lat'=>5, 'lng'=>1],.. ] or string "4,5|5,6|..."
	 * @param mixed $poly
	 *
	 * @throws Exception
	 */
	public function __construct($poly) {
		$this->poly = [];
		if (is_array($poly)) {
			if (count($poly) < 3) {
				throw new Exception('Polygon must have at least 3 points');
			}
			// analyse first element
			$analyse = $poly[0];
			if ($analyse instanceof Coord) {
				// array of Coords. We like it!
				$this->poly = $poly;
			}
			elseif (is_array($analyse) && isset($analyse['lat'])) {
				// array of [[lat=>.. lng=>..][lat=> ]
				foreach ($poly as $coord) {
					$this->poly[] = new Coord($coord['lat'], $coord['lng']);
				}
			}
			elseif (is_array($analyse) && count($analyse) == 2) {
				// array of [ [lat,lng][lat,lng] ]
				// or is it lng,lat
				$test = $poly[0];
				$llorder = 'latlon';
				if ($test[0] < 10 && $test[1] > 30) {
					$llorder = 'lonlat';
				}
				foreach ($poly as $coord) {
					if ($llorder == 'lonlat') {
						$this->poly[] = new Coord($coord[1], $coord[0]);
					}
					else {
						$this->poly[] = new Coord($coord[0], $coord[1]);
					}
				}
			}
			else {
				throw new Exception('Cant construct polygon');
			}
		}
		elseif (is_string($poly)) {
			if (stripos($poly, 'polygon') !== false) {
				// WKT POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))
				$this->poly = $this->wkt2polygon($poly);
			}
			else {
				// format lat,lng|lat,lng|...
				$aPoly = explode('|', $poly);
				if (count($aPoly) < 3) {
					throw new Exception('Polygon must have at least 3 points');
				}
				foreach ($aPoly as $coord) {
					$aCoord = explode(',', $coord);
					if (count($aCoord) == 2) {
						$this->poly[] = new Coord($aCoord[0], $aCoord[1]);
					}
				}
				if (count($this->poly) < 3) {
					throw new Exception('Polygon must have at least 3 points');
				}
			}
		}
		else {
			throw new Exception('Cant construct polygon on this type');
		}
		// make sure to remove the "end" closing(s!) if it is exactly the same as the begin
		while (count($this->poly) >= 4 && $this->poly[0]->equals($this->poly[count($this->poly) - 1])) {
			array_pop($this->poly);
		}
	}

	/**
	 * a valid polygon contains at least 2 vertices
	 * @return bool
	 */
	public function valid() {
		return count($this->poly) > 2;
	}

	/**
	 * size as defined by the number of nodes
	 * @return int
	 */
	public function size() {
		return count($this->poly);
	}

	/**
	 * Format the polygon into a serialisation string 51.3,5.3|51.4,6.5|...
	 * @return string
	 */
	public function polyString() {
		$rv = '';
		foreach ($this->poly as $coord) {
			$rv .= $coord->toString().'|';
		}

		return $rv;
	}

	/**
	 * Format into a WKT string POLYGON((...))
	 * use WICKET as viewer
	 * http://arthur-e.github.io/Wicket/sandbox-gmaps3.html
	 * @return string
	 */
	public function polyWktString() {
		$rv = 'POLYGON((';
		/** @var Coord $coord */
		foreach ($this->poly as $coord) {
			$rv .= $coord->toWktString().',';
		}
		// close by copying first point
		$rv .= $this->poly[0]->toWktString();
		$rv .= '))';

		return $rv;
	}

	/**
	 * Format into a GeoJson structure (not a json string, but the php array version)
	 * @return array - php array representing the geoJson structure
	 */
	public function polyGeoJsonArray() {
		$coords = [];
		foreach ($this->poly as $coord) {
			$coords[] = [floatval($coord->longitude), floatval($coord->latitude)];
		}
		// close by adding first
		$coords[] = [floatval($this->poly[0]->longitude), floatval($this->poly[0]->latitude)];

		$rv = ['type' => 'Feature',
				'geometry' => [
					'type' => 'Polygon',
					'coordinates' => [$coords],
			],
			'properties' => [],
		];

		return $rv;
	}

	/**
	 * Convert to a GeoJson string. Option for pretty-printing the string repr.
	 * @param bool $pretty
	 * @return false|string
	 */
	public function polyGeoJsonString($pretty = false) {
		return json_encode($this->polyGeoJsonArray(), $pretty ? JSON_PRETTY_PRINT : 0);
	}

	/**
	 * Convert to a Lat - Lng array  [ ['lat'=>...,'lng'=>...] ... ]
	 * @return array
	 */
	public function polyLatLngArray() {
		$rv = [];
		/** @var Coord $coord */
		foreach ($this->poly as $coord) {
			$rv[] = ['lat' => $coord->latitude, 'lng' => $coord->longitude];
		}

		return $rv;
	}

	/**
	 * simple clone
	 * @return Polygon
	 */
	public function clone() {
		return clone $this;
	}

	/**
	 * Two polygons are equal when each vertex is within approx 10 cm
	 * @param $other
	 * @return bool
	 */
	public function equals($other) {
		if (is_null($other))            return false;
		if ($other == $this)            return true;
		if (!($other instanceof self))  return false;

		return $this->polyString() == $other->polyString(); // just compare their polystrings
	}

	/**
	 * fast function to determine "far" away from polygon to avoid complex 'contains' calculation
	 * @param $c
	 * @return bool
	 */
	public function farAway(Coord $c) {
		return !$this->valid() || $this->poly[0]->distance($c) > 15000; // 15 km apart from first element is "far" away
	}

	/**
	 * Does the poly contain a coordinate
	 * @param Coord point
	 * @return bool
	 */
	public function contains(Coord $point) {
		if (!$this->valid()) {
			return false;
		}
		$pointJ = $this->poly[count($this->poly) - 1];       // first test last against first
		$contains = false;
		foreach ($this->poly as $pointI) {
			if ((($pointI->longitude >= $point->longitude) != ($pointJ->longitude >= $point->longitude)) &&        // point longitude is inbetween both polyoints. If pI.lng and pJ.lng are both equal, this is never true. So we never get a div-by-zero in the line below
				($point->latitude <= ($pointJ->latitude - $pointI->latitude) * ($point->longitude - $pointI->longitude) / ($pointJ->longitude - $pointI->longitude) + $pointI->latitude)) {
				$contains = !$contains;
			}
			$pointJ = $pointI;   // now test next against previous
		}
		return $contains;
	}

	/**
	 * closest distance in meters. If point inside polygon, 0 is returned
	 * @param Coord $point
	 * @return float
	 */
	public function distanceToPoint(Coord $point): float {
		if ($this->contains($point))
			return 0.0;
		$rv = INF;
		// get the min distance between the point and each polygon point
		foreach ($this->poly as $pI) {
			$dist = $point->distance($pI);
			if ($dist < $rv) {
				$rv = $dist;
			}
		}
		return $rv;
	}

	/**
	 * closest distance in meters. Determine the outer radiuses and return the nearest distance
	 * for overlapping polygons the distance is 0.
	 * @param Coord $point
	 * @return float
	 */
	public function distanceToPolygon(Polygon $other): float {
		$centerDistance = $this->center()->distance($other->center());
		$centerDistance -= $this->smallestOuterCircleRadius();
		$centerDistance -= $other->smallestOuterCircleRadius();
		return $centerDistance<0 ? 0 : $centerDistance;
	}

	/**
	 * Shorthand to calculate the closest distance to any Geometric object
	 * @param mixed $polyOrCoord
	 * @throws Exception
	 * @return float
	 */
	public function distance($polyOrCoord) {
		if (is_object($polyOrCoord) && $polyOrCoord instanceof Coord)
			return $this->distanceToPoint($polyOrCoord);
		if (is_object($polyOrCoord) && $polyOrCoord instanceof Polygon)
			return $this->distanceToPolygon($polyOrCoord);
		throw new Exception("Cant calculate the distance of this value");
	}
	/**
	 * Signed area of polygon in square 100m2 (needed for center) (can be negative dep. on CW / CCW )
	 * Note: 1 lat/long degree is about 100km. So the result is in square 100km. Use ringarea to get meters
	 * @return float
	 */
	public function signedArea() {
		// https://en.wikipedia.org/wiki/Shoelace_formula
		if (!$this->valid()) {
			return 0.0;
		}
		$sum = 0.0;
		for ($i = 0; $i < count($this->poly); $i++) {
			$next = ($i < count($this->poly) - 1) ? $i + 1 : 0;    // make it a loop
			$sum += $this->poly[$i]->longitude * $this->poly[$next]->latitude - $this->poly[$next]->longitude * $this->poly[$i]->latitude;
		}
		return $sum / 2;
	}

	/**
	 * https://github.com/spinen/laravel-geometry
	 * Estimate the area of a ring
	 *
	 * Calculate the approximate area of the polygon were it projected onto
	 *     the earth.  Note that this area will be positive if ring is oriented
	 *     clockwise, otherwise it will be negative.
	 *
	 * Reference:
	 * Robert. G. Chamberlain and William H. Duquette, "Some Algorithms for
	 *     Polygons on a Sphere", JPL Publication 07-03, Jet Propulsion
	 *     Laboratory, Pasadena, CA, June 2007 http://trs-new.jpl.nasa.gov/dspace/handle/2014/40409
	 * https://sgp1.digitaloceanspaces.com/proletarian-library/books/5cc63c78dc09ee09864293f66e2716e2.pdf
	 * @return float
	 * @see https://github.com/mapbox/geojson-area/blob/master/index.js#L55
	 */
	public function ringArea() {
		if (!$this->valid()) {
			return 0.0;
		}
		$area = 0.0;
		$length = count($this->poly);

		for ($i = 0; $i < count($this->poly); $i++) {
			$point1 = $this->poly[$i];
			$point2 = $this->poly[($i + 1) % $length];
			$point3 = $this->poly[($i + 2) % $length];

			$area += (deg2rad($point3->longitude) - deg2rad($point1->longitude)) * sin(deg2rad($point2->latitude));
		}
		// labda = longitude, phi = latitude
		// A = (- R^2 / 2) * sum[0..n-1]=>(  (labda(n+1) - labda(n-1)) * sin ( phi(n) ) )
		return $area * 6378137 * 6378137 / 2;
	}

	/**
	 * real area is always positive
	 * @return
	 */
	public function areaSquareMeters() {
		return abs($this->ringArea());
	}

	/**
	 * calculate the center
	 * @return Coord $coord
	 */
	public function center() {
		// https://en.wikipedia.org/wiki/Centroid#Of_a_polygon
		if (!$this->valid()) {
			return null;
		}
		$parea = $this->signedArea();
		// note: parea is in 100km2 areas (approx 1 lat/lng degree) so very small like 1e-10 for 1m2
		if (abs($parea) < 1E-15) {
			return $this->poly[0];
		}    // too small, the center is equal to the first point
		$x = 0.0;
		$y = 0.0;
		for ($i = 0; $i < count($this->poly); $i++) {
			$next = ($i < count($this->poly) - 1) ? $i + 1 : 0;    // make it a loop

			$secondFac = $this->poly[$i]->longitude * $this->poly[$next]->latitude - $this->poly[$next]->longitude * $this->poly[$i]->latitude;
			$x += ($this->poly[$i]->longitude + $this->poly[$next]->longitude) * $secondFac;
			$y += ($this->poly[$i]->latitude + $this->poly[$next]->latitude) * $secondFac;
		}
		// divide by 6x area

		$x = $x / (6 * $parea);
		$y = $y / (6 * $parea);

		return new Coord($y, $x);
	}

	/**
	 * get the outer circumference of a polygon
	 * @return float
	 */
	public function smallestOuterCircleRadius() {
		/// just use the farthest point around the polycenter
		/// Should use "smallest outer circle" algorithm https://math.stackexchange.com/questions/2671307/existence-of-smallest-circle-containing-a-polygon
		/// implementation: https://www.nayuki.io/page/smallest-enclosing-circle which could lead to an even smaller circle
		$r = 0.1; // at least to avoid zero
		$center = $this->center();
		if (is_null($center)) {
			return 0.1;
		}

		// get the max distance between center and each polygon point
		foreach ($this->poly as $pI) {
			$dist = $center->distance($pI);
			if ($dist > $r) {
				$r = $dist;
			}
		}

		return $r;
	}

	/**
	 * largest inner circle against the center
	 * @return
	 */
	public function largestInnerCircleRadius() {
		$center = $this->center();
		if (is_null($center)) {
			return 0;
		}
		// now calculate the distance to the closest point
		$radius = 99999999999.0;
		foreach ($this->poly as $pI) {
			$radius = min($pI->distance($center), $radius);
		}

		return $radius;
	}

	/**
	 * Factory to get an expanded new polygon by expand meters in all directions outwards (positive by)
	 * @param mixed $expand
	 * @return
	 */
	public function expand($expand) {
		// 111111 meters = 1 degree, so 1 meter = 1/111111 degree (1.000009000009 or 1.00001)
		// let scale = 1.0 + (1/111111)*expandMeters // not correct to multiply as dLat is not in
		$center = $this->center();
		if (is_null($center)) {
			return 0;
		}
		$expandedPoly = [];

		for ($i = 0; $i < count($this->poly); $i++) {
			$bearing = $center->bearing($this->poly[$i]);                      // from center towards pI
			$newPoint = $this->poly[$i]->movedClone($expand, $bearing);         // make a COPY and move
			$expandedPoly[] = $newPoint;
		}

		return new self($expandedPoly);
	}

	/**
	 * Factory to get an new polygon by moving the polygon a number of meters in a direction
	 * @param $distance
	 * @param $bearing
	 * @throws Exception
	 * @return Polygon
	 */
	public function movedClone($distance, $bearing): Polygon {
		$movedPoly = [];

		for ($i = 0; $i < count($this->poly); $i++) {
			$newPoint = $this->poly[$i]->movedClone($distance, $bearing);         // make a COPY and move
			$movedPoly[] = $newPoint;
		}

		return new self($movedPoly);
	}

	/**
	 * @param float $tolerance      - distance to be considered to simplify (0.000001=1dm 0.00001=1m 0.0001=10m)
	 * @param bool  $highestQuality
	 * @return Polygon
	 */
	public function simplify($tolerance = 0.0001, $highestQuality = true) {
		if (count($this->poly) < 10) {
			// Log::warning("Polygon: No simplify ".$this->polyWktString()." less then 10 points");
			return new self($this->polyString());  // clone
		}
		// tolerance = distance of p
		$sqTolerance = $tolerance * $tolerance;
		$points = $this->polyLatLngArray();   // convert to  [[lat=>1,lng=>1],[lat=>2,lng=>3]]
		if (!$highestQuality) {
			// if highest not needed, simplify further
			$points = $this->simplifyRadialDistance($points, $sqTolerance);
		}
		$points = $this->simplifyDouglasPeucker($points, $sqTolerance);
		try {
			$newPoly = new self($points);
		} catch (Exception $e) {
			// Log::error("Polygon: Could not simplify ".$this->polyWktString()." error: ".$e->getMessage());
			return new self($this->polyString());  // clone
		}

		return $newPoly;
	}

	//*********************************************************************************************************************
	// private parts
	//*********************************************************************************************************************
	// SIMPLITICATION
	private function simplifyRadialDistance($points, $sqTolerance) {
		$prevPoint = $points[0];
		$newPoints = [$prevPoint];
		$point = null;

		for ($i = 1, $len = count($points); $i < $len; $i++) {
			$point = $points[$i];
			if ($this->getSqDist($point, $prevPoint) > $sqTolerance) {
				$newPoints[] = $point;
				$prevPoint = $point;
			}
		}

		if ($prevPoint !== $point) {
			$newPoints[] = $point;
		}
		return $newPoints;
	}

	// square distance between 2 points
	private function getSqDist($p1, $p2) {
		$dx = $p1['lng'] - $p2['lng'];
		$dy = $p1['lat'] - $p2['lat'];

		return $dx * $dx + $dy * $dy;
	}

	// simplification using optimized Douglas-Peucker algorithm with recursion elimination
	private function simplifyDouglasPeucker($points, $sqTolerance) {
		$len = count($points);
		$markers = array_fill(0, $len - 1, null);
		$first = 0;
		$last = $len - 1;
		$stack = [];
		$newPoints = [];
		$index = null;

		$markers[$first] = $markers[$last] = 1;

		while ($last) {
			$maxSqDist = 0;

			for ($i = $first + 1; $i < $last; $i++) {
				$sqDist = $this->getSqSegDist($points[$i], $points[$first], $points[$last]);
				if ($sqDist > $maxSqDist) {
					$index = $i;
					$maxSqDist = $sqDist;
				}
			}

			if ($maxSqDist > $sqTolerance) {
				$markers[$index] = 1;
				array_push($stack, $first, $index, $index, $last);
			}

			$last = array_pop($stack);
			$first = array_pop($stack);
		}

		//var_dump($markers, $points, $i);
		for ($i = 0; $i < $len; $i++) {
			if ($markers[$i]) {
				$newPoints[] = $points[$i];
			}
		}

		return $newPoints;
	}

	// square distance from a point to a segment
	private static function getSqSegDist($p, $p1, $p2) {
		// longitude = x latitude = y
		$x = $p1['lng'];
		$y = $p1['lat'];
		$dx = $p2['lng'] - $x;
		$dy = $p2['lat'] - $y;

		if (intval(100000 * $dx) !== 0 || intval(100000 * $dy) !== 0) {
			$t = (($p['lng'] - $x) * $dx + ($p['lat'] - $y) * $dy) / ($dx * $dx + $dy * $dy);

			if ($t > 1) {
				$x = $p2['lng'];
				$y = $p2['lat'];
			}
			elseif ($t > 0) {
				$x += $dx * $t;
				$y += $dy * $t;
			}
		}

		$dx = $p['lng'] - $x;
		$dy = $p['lat'] - $y;

		return $dx * $dx + $dy * $dy;
	}

	// Convert String in WKT form to a Coord array
	private function wkt2polygon($wkt) {
		// Example WKT: POLYGON((4.347604 51.930035, 4.347561 51.930106, 4.347283 51.93004, 4.347327 51.92997, 4.347604 51.930035))
		if (!preg_match('/POLYGON\s+\(\((.*)\)\)/i', $wkt, $matches)) {
			throw new Exception("Cant construct polygon from WKT $wkt");
		}
		$rv = [];
		$nodes = explode(',', $matches[1]);
		if (count($nodes) < 4) {
			throw new Exception("Invalid WKT POLYGON definion (less than 4 points): $wkt");
		}

		if (trim($nodes[0]) == trim(end($nodes))) {
			//array_shift($nodes);	// remove first
			array_pop($nodes);    // remove last
		}
		//else
		//    Log::error("Invalid WKT POLYGON definion (last/first points differ ) (ignored): $wkt");

		foreach ($nodes as $node) {
			// WKT uses LON/LAT instead of LAT/LONG
			list($lon, $lat) = explode(' ', trim($node));    // php7.1: [$lon,$lat]
			$rv[] = new Coord($lat, $lon);
		}
		return $rv;
	}
}
