<?php
use PHPUnit\Framework\TestCase;
use Rvwoens\Geometry\Coord;

/**
 * Class CoordsTest
 *
 * @version 1.0
 * @Author Ronald vanWoensel <rvw@cosninix.com>
 */
class CoordsTest extends TestCase
{
	public function testInstantiationOfCoord() {
		$obj = new Coord(52, 5);
		$this->assertInstanceOf('\Rvwoens\Geometry\Coord', $obj);
		$this->assertEqualsWithDelta(52, $obj->latitude, 1E-5, 'Latitude is not ok');
		$this->assertEqualsWithDelta(5, $obj->longitude, 1E-5, 'Latitude is not ok');
	}

	public function testDistanceCorrect() {
		$p1 = new Coord(52.773767, 6.180272);
		$p2 = new Coord(52.773581, 6.180304);
		$this->assertEquals($p1->distance($p2), 20.8, 'distance error', 0.1);
		$this->assertEquals($p2->distance($p1), 20.8, 'distance error', 0.1);
	}

	public function testBearingCorrect() {
		$p1 = new Coord(0, 0);
		$p2 = new Coord(0.00001, 0); // move north
		$this->assertEquals($p1->bearing($p2), 0, 'bearing error', 0.001);
		$this->assertEquals($p2->bearing($p1), 180, 'bearing error', 0.001);

		$p3 = new Coord(-0.00001, 0); // move south
		$this->assertEquals($p1->bearing($p3), 180, 'bearing error', 0.001);
		$this->assertEquals($p3->bearing($p1), 0, 'bearing error', 0.001);

		$p4 = new Coord(0, 0.00001); // move east
		$this->assertEquals($p1->bearing($p4), 90, 'bearing error', 0.001);
		$this->assertEquals($p4->bearing($p1), 270, 'bearing error', 0.001);

		$p5 = new Coord(52.773767, 6.180272);
		$p6 = new Coord(52.773767, 6.180372);    // east
		$this->assertEquals($p5->bearing($p6), 90, 'bearing error', 0.001);
		$this->assertEquals($p6->bearing($p5), 270, 'bearing error', 0.001);
	}

	public function testMoveCorrect() {
		$p1 = new Coord(0, 0);
		$p2 = $p1->movedClone(112, 0); // moved  distance 112 m north bearin 0
		$this->assertEquals($p2->latitude, 0.0010069560824378324, 'move error', 0.001);
		$this->assertEquals($p2->longitude, 0, 'move error', 0.001);

		$p3 = new Coord(52.673767, 6.280272);
		$p4 = $p3->movedClone(15, 190);		// distance 15 meter, bearing 190 degrees
		$this->assertEquals($p4->latitude, 52.67363418863339, 'move error', 0.001);
		$this->assertEquals($p4->longitude, 6.280233289986495, 'move error', 0.001);
	}

	public function testRound() {
		$p1 = new Coord(52.773767, 6.180272);
		$p2 = new Coord(52.773767499999, 6.180272499999);
		$dist = $p1->distance($p2);
		$this->assertEqualsWithDelta(0.05, $dist, 0.02);
		$p2->round();
		$dist = $p1->distance($p2);
		$this->assertEqualsWithDelta(0, $dist, 0.00001);
	}

	public function testRd() {
		// RD: X=228638 = longitude  Y=619487 = latitude
		$p1= new Coord(619487, 228638);  // northest part of NL
		$this->assertTrue($p1->isRDcoord());
		$p2 = $p1->makeWGS84fromRD();
		echo "\nRD $p1->latitude,$p1->longitude WGS $p2->latitude,$p2->longitude\n";
		$this->assertEqualsWithDelta(6.49835, $p2->longitude, 1E-5);
		$this->assertEqualsWithDelta(53.55632, $p2->latitude, 1E-5);

		// RD: X=228638 = longitude  Y=619487 = latitude
		$p1= new Coord(228638, 619487);  // northest part of NL.. X/Y mixed
		$this->assertTrue($p1->isRDcoord());
		$p2 = $p1->makeWGS84fromRD();
		echo "\nRD $p1->latitude,$p1->longitude WGS $p2->latitude,$p2->longitude\n";
		$this->assertEqualsWithDelta(6.49835, $p2->longitude, 1E-5);
		$this->assertEqualsWithDelta(53.55632, $p2->latitude, 1E-5);

		// RD: Y = latitude X = longitude
		//              lat    lng
		$p1= new Coord(463000, 155000);  // referentiepunt RD
		$this->assertTrue($p1->isRDcoord());
		$p2 = $p1->makeWGS84fromRD();
		// should be 52.15517, 5.38721
		echo "\nRD $p1->latitude,$p1->longitude WGS $p2->latitude,$p2->longitude\n";
		$this->assertEqualsWithDelta(5.38721, $p2->longitude, 1E-5);
		$this->assertEqualsWithDelta(52.15517, $p2->latitude, 1E-5);

		// RD: Y = latitude X = longitude
		//              lat    lng
		$p1= new Coord(317985, 176395);  // centrum maastricht
		$this->assertTrue($p1->isRDcoord());
		$p2 = $p1->makeWGS84fromRD();
		// should be 50.851302, 5.690996
		echo "\nRD $p1->latitude,$p1->longitude WGS $p2->latitude,$p2->longitude\n";
		$this->assertEqualsWithDelta(5.690996, $p2->longitude, 1E-5);
		$this->assertEqualsWithDelta(50.851302, $p2->latitude, 1E-5);

		// RD: Y = latitude X = longitude
		//              lat    lng
		$p1= new Coord(377338, 17227);  // cadzand
		$this->assertTrue($p1->isRDcoord());
		$p2 = $p1->makeWGS84fromRD();
		// should be 51.368415, 3.408606
		echo "\nRD $p1->latitude,$p1->longitude WGS $p2->latitude,$p2->longitude\n";
		$this->assertEqualsWithDelta(3.408606, $p2->longitude, 1E-5);
		$this->assertEqualsWithDelta(51.368415, $p2->latitude, 1E-5);
	}
}
