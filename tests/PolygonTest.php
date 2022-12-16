<?php
use PHPUnit\Framework\TestCase;
use Rvwoens\Geometry\Coord;
use Rvwoens\Geometry\Polygon;

class PolygonTest extends TestCase
{
	public function testInit() {
		$test = [
				new Coord(1, 1),
				new Coord(2, 1),
				new Coord(2, 2),
				new Coord(1, 2),
		];
		$p1 = new Polygon($test);

		// test with string init
		$p2 = new Polygon('1,1|2,1|2,2|1,2');

		$this->assertTrue($p1->equals($p2));

		$test3 = [[1,1],[2,1],[2,2],[1,2]];
		$p3 = new Polygon($test3);
		$this->assertTrue($p3->equals($p1));

		$test4 = [['lat' => 1,'lng' => 1], ['lat' => 2, 'lng' => 1], ['lat' => 2, 'lng' => 2], ['lat' => 1, 'lng' => 2]];
		$p4 = new Polygon($test4);
		$this->assertTrue($p4->equals($p1));
	}

	public function testTostring() {
		$test = [
				new Coord(1, 1),
				new Coord(2, 1),
				new Coord(2, 2),
				new Coord(1, 2),
		];
		$p1 = new Polygon($test);
		//System.out.println("p1 polystring: "+p1.polyString());
		$this->assertEquals($p1->polyString(), '1.000000,1.000000|2.000000,1.000000|2.000000,2.000000|1.000000,2.000000|');
	}

	public function testCenter() {
		$p1 = new Polygon('1,1|2,1|2,2|1,2');
		$this->assertTrue($p1->center()->equals(new Coord(1.5, 1.5)));
		// lat   g
		// 5   +--+
		// 4   | b| f
		// 3  c|  +--+
		// 2   |  a  | d
		// 1   +-----+
		// |        e
		// |---1--2--3--->long
		$hf = new Polygon('1,1|1,3|3,3|3,2|5,2|5,1');
		$center = $hf->center();
		//System.out.println("hf: "+hf);
		$this->assertEquals($center, new Coord(2.6666666666666665, 1.8333333333333333), 'center', 0.000001);
		$p3 = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		$center3 = $p3->center();
		$this->assertEquals($center3, new Coord(52.77353314193018, 6.1802023372608526), 'center', 0.000001); // xCode calculated value
	}

	public function testOutercircle() {
		$p1 = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		// something like 28.92 meters
		$radius = $p1->smallestOuterCircleRadius();
		$this->assertEquals(28.92, $radius, 'outercircle', 0.1);
	}

	public function testInnercircle_isCorrect() {
		$p1 = new Polygon('52,6|52.001,6|52.001,6.001|52,6.001|');
		$radius = $p1->largestInnerCircleRadius();
		$center = $p1->center();
		$this->assertEquals($center, new Coord(52.0005, 6.0005), 'inner', 0.001); // xCode calculated value
		$this->assertEquals(65.38, $radius, 'inner', 0.1);
	}

	public function testExpand() {
		$p1 = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		$p2 = $p1->expand(1);
		$radius = $p2->smallestOuterCircleRadius();
		$this->assertEquals(30.0, $radius, 'e4xpand', 0.1);

		$p3 = new Polygon('1,1|1.00001,1|1.00001,1.00001|1,1.00001|');
		$p4 = $p3->expand(1);
		$this->assertEquals($p4->polyString(), '0.999994,0.999994|1.000016,0.999994|1.000016,1.000016|0.999994,1.000016|');
	}

	public function testPolyContains() {
		// This is an example of a functional test case.
		// Use XCTAssert and related functions to verify your tests produce the correct results.
		$hf = new Polygon('1,1|2,1|2,2|1,2');
		$coord = new Coord(1.5, 1.5);
		$this->assertTrue($hf->contains($coord));
		$coord1 = new Coord(2.5, 2.5);
		$this->assertFalse($hf->contains($coord1));
		$coord2 = new Coord(0.8, 1.5);
		$this->assertFalse($hf->contains($coord2));
		$coord3 = new Coord(2.8, 1.5);
		$this->assertFalse($hf->contains($coord3));
		$coord4 = new Coord(0.8, 0.999);
		$this->assertFalse($hf->contains($coord4));
	}

	public function testPolyContainsCCW() {
		// This is an example of a functional test case.
		// Use XCTAssert and related functions to verify your tests produce the correct results.
		$hf = new Polygon('1,1|1,2|2,2|2,1');
		$coord = new Coord(1.5, 1.5);
		$this->assertTrue($hf->contains($coord));
		$coord1 = new Coord(2.5, 2.5);
		$this->assertFalse($hf->contains($coord1));
		$coord2 = new Coord(0.8, 1.5);
		$this->assertFalse($hf->contains($coord2));
		$coord3 = new Coord(2.8, 1.5);
		$this->assertFalse($hf->contains($coord3));
		$coord4 = new Coord(0.8, 0.999);
		$this->assertFalse($hf->contains($coord4));
	}

	public function testComplexPolyContains() {
		//       g
		// 5   +--+
		// 4   | b| f
		// 3  c|  +--+
		// 2   |  a  | d
		// 1   +-----+
		// |        e
		// |---1--2--3--->
		$hf = new Polygon('1,1|3,1|3,3|2,3|2,5|1,5|');

		$coorda = new Coord(2, 2);
		$this->assertTrue($hf->contains($coorda));
		$coordb = new Coord(1.5, 4);
		$this->assertTrue($hf->contains($coordb));
		$coordc = new Coord(0.8, 3);
		$this->assertFalse($hf->contains($coordc));
		$coordd = new Coord(3.1, 2);
		$this->assertFalse($hf->contains($coordd));
		$coorde = new Coord(2.5, 0.99);
		$this->assertFalse($hf->contains($coorde));
		$coordf = new Coord(2.5, 4);
		$this->assertFalse($hf->contains($coordf));
		$coordg = new Coord(1.5, 6);
		$this->assertFalse($hf->contains($coordg));
	}

	public function testComplexPolyContainsCCW() {
		//       g
		// 5   +--+
		// 4   | b| f
		// 3  c|  +--+
		// 2   |  a  | d
		// 1   +-----+
		// |        e
		// |---1--2--3--->
		$hf = new Polygon('1,1|1,5|2,5|2,3|3,3|3,1|');

		$coorda = new Coord(2, 2);
		$this->assertTrue($hf->contains($coorda));
		$coordb = new Coord(1.5, 4);
		$this->assertTrue($hf->contains($coordb));
		$coordc = new Coord(0.8, 3);
		$this->assertFalse($hf->contains($coordc));
		$coordd = new Coord(3.1, 2);
		$this->assertFalse($hf->contains($coordd));
		$coorde = new Coord(2.5, 0.99);
		$this->assertFalse($hf->contains($coorde));
		$coordf = new Coord(2.5, 4);
		$this->assertFalse($hf->contains($coordf));
		$coordg = new Coord(1.5, 6);
		$this->assertFalse($hf->contains($coordg));
	}

	public function testComplexPolyVoortuin() {
		$hf = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);

		$coorda = new Coord(52.773502, 6.180314);
		$this->assertTrue($hf->contains($coorda));
		$coordb = new Coord(52.773617, 6.180163);
		$this->assertTrue($hf->contains($coordb));
		$coordc = new Coord(52.773596, 6.180311);
		$this->assertFalse($hf->contains($coordc));
	}

	public function testAreaVoortuin() {
		$hf = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		$area = $hf->areaSquareMeters();
		$this->assertEquals(794.45, $area, 'voortuinarea', 0.1);

		// test: paste into geojson.io -> 794.45m2
		//echo "\n\n".$hf->polyGeoJsonString()."\n";
	}

	public function testPolyConcave() {
		//
		// 11            x
		// 10       ___-- \
		// 9  ___---       \
		// 8 x               \
		// 7  \    __-x       \
		// 6    x--     \      \
		// 5          __ x       \
		// 4     __---          __x
		// 3    x         ___---
		// 2     \  ___---
		// 1       x
		// |
		// |-0--1--2--3--4--5--6--7-->
		$hf = new Polygon('1,2|4,7|11,4|8,0|6,1|7,3|5,4|3,1|');

		$coorda = new Coord(2, 2);
		$this->assertTrue($hf->contains($coorda));
		$coordb = new Coord(3, 4);
		$this->assertTrue($hf->contains($coordb));

		$this->assertFalse($hf->contains(new Coord(5, 3)));
		$this->assertFalse($hf->contains(new Coord(6.5, 3)));
		$this->assertTrue($hf->contains(new Coord(7.01, 3)));
		$this->assertTrue($hf->contains(new Coord(6.9, 2)));
		$this->assertTrue($hf->contains(new Coord(8, 3)));
		$this->assertFalse($hf->contains(new Coord(10.5, 3)));

		$this->assertFalse($hf->contains(new Coord(0.8, 3)));
		$this->assertTrue($hf->contains(new Coord(3.1, 2)));
		$this->assertFalse($hf->contains(new Coord(2.5, 0.99)));
		$this->assertFalse($hf->contains(new Coord(2.5, 5)));
		$this->assertFalse($hf->contains(new Coord(1.5, 6)));
		$this->assertFalse($hf->contains(new Coord(1.5, 6)));
		$this->assertFalse($hf->contains(new Coord(9, 6)));
	}

	public function testContainsUilenbos() {
		// 718661
		// 52.777413,6.175508 does NOT contain
		//{"id":718661,"poly":"52.773295,6.179491|52.776129,6.186224|52.781629,6.180451|52.779042,6.173573|"}
		$hf = new Polygon('52.773295,6.179491|52.776129,6.186224|52.781629,6.180451|52.779042,6.173573|');
		$dasburgt = new Coord(52.777413, 6.175508);
		$this->assertTrue($hf->contains($dasburgt));
	}

	public function testValid8() {
		$ill = [
			new Coord(1, 1), new Coord(2, 2), new Coord(3, 3),
		];
		$i = new Polygon($ill);
		$this->assertTrue($i->valid());
	}

	public function testDistanceToCoord() {
		$p1 = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		$c1 = new Coord(52.773351978321, 6.1804309267635);
		$c1->move(140, 90);	// move x meters to the east
		$distance = $p1->distanceToPoint($c1);
		$this->assertEqualsWithDelta(140, $distance, 1, "Distance to coord");
	}
	public function testDistanceToPoly() {
		$p1 = new Polygon(
			'52.773351978321,6.180108127987|52.773389299363,6.1804309267635|'.
			'52.773571452384,6.180429585659|52.773564556135,6.1802386819716|'.
			'52.773654105861,6.1802271658714|52.773654790414,6.180183718435|'.
			'52.773770682493,6.1801657519843|52.773763253848,6.1800029462468|'.
			'52.773636561026,6.1800332595583|52.773547924003,6.1800450960751|'
		);
		$p2 = $p1->movedClone(140, 90);				// move x meters to the east
		$distance = $p1->distanceToPolygon($p2);	// less than 140 as we compare the east size of p1 to the west side of p2
		$this->assertEqualsWithDelta(80, $distance, 5, "Distance to coord");
	}

	public function testCombine() {
		//       g
		// 5   +--+
		// 4   | b| f    Clockwise
		// 3  c|  +--+
		// 2   |  a  | d
		// 1   +-----+
		// |        e
		// |---1--2--3--->
		$hf = new Polygon('1,1|1,5|2,5|2,3|3,3|3,1|');
		//
		// 3            +--+   Clockwise
		// 2            |  |
		// 1            +--+
		// |---1--2--3--4--5->
		$add = new Polygon('4,1|4,3|5,3|5,1|');
		$combi = $hf->makeCombined($add);
		$combistr=$combi->polyString();
		//       g
		// 5   +--+
		// 4   | b| f
		// 3  c|  +--+===+---+
		// 2   |  a  |   |   |
		// 1   +-----+   +---+
		// |        e
		// |---1--2--3---4---5>
		$this->assertEquals('1.000000,1.000000|1.000000,5.000000|2.000000,5.000000|2.000000,3.000000|3.000000,3.000000|4.000000,3.000000|'.
							'5.000000,3.000000|5.000000,1.000000|4.000000,1.000000|4.000000,3.000000|3.000000,3.000000|3.000000,1.000000|', $combistr);
	}
}
