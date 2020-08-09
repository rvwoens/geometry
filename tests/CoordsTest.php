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
	public function testInstantiationOfCoord()
	{
		$obj = new Coord(52, 5);
		$this->assertInstanceOf('\Rvwoens\Geometry\Coord', $obj);
		$this->assertEqualsWithDelta(52, $obj->latitude, 1E-5, 'Latitude is not ok');
		$this->assertEqualsWithDelta(5, $obj->longitude, 1E-5, 'Latitude is not ok');
	}

	public function testDistanceCorrect()
	{
		$p1 = new Coord(52.773767, 6.180272);
		$p2 = new Coord(52.773581, 6.180304);
		$this->assertEquals($p1->distance($p2), 20.8, 'distance error', 0.1);
		$this->assertEquals($p2->distance($p1), 20.8, 'distance error', 0.1);
	}

	public function testBearingCorrect()
	{
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

	public function testMoveCorrect()
	{
		$p1 = new Coord(0, 0);
		$p2 = $p1->movedClone(112, 0); // moved  distance 112 m north bearin 0
		$this->assertEquals($p2->latitude, 0.0010069560824378324, 'move error', 0.001);
		$this->assertEquals($p2->longitude, 0, 'move error', 0.001);

		$p3 = new Coord(52.673767, 6.280272);
		$p4 = $p3->movedClone(15, 190);		// distance 15 meter, bearing 190 degrees
		$this->assertEquals($p4->latitude, 52.67363418863339, 'move error', 0.001);
		$this->assertEquals($p4->longitude, 6.280233289986495, 'move error', 0.001);
	}
}
