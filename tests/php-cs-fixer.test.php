<?php namespace A\B\c;

/*
 * Some dummy code to test php-cs-fixer
 */
use N\ClassName;
use N\AnotherClassName;
use N\OneMoreClassName;

$instance = new class {
};

namespace A {
	/**
	 * @return int
	 */
	function foo() { return 0; }

	/**
<<<<<<< HEAD
     *
=======
>>>>>>> 8107a96f4648811531d254f4931a98e3bdf21d9e
	 * barrrr
	 * @param $x
	 * @param $y
	 * @param int $z
	 */
	function bar($x,
				 $y, int $z = 1) {
		$x = 0;
		// $x = 1
		do {
			$y += 1;
		} while ($y < 10);
		if (true) $x = 10;
		elseif ($y < 10) $x = 5;
		elseif (true) $x = 5;
		for ($i = 0; $i < 10; $i++) $yy = $x > 2 ? 1 : 2;
		while (true) $x = 0;
		do {
			$x += 1;
		} while (true);
		foreach ([  "a" => 0, "b" => 1,
					"c" => 2] as $e1) {
			echo $e1;
		}
		$count = 10;
		$x = ["x", "y",
				[   1 => "abc",
					2 => "def", 3 => "ghi"]
			];
		$zz = [0.1, 0.2,
					0.3, 0.4];
		$x = [
			0 => "zero",
			123 => "one two three",
			25 => "two five"
		];
		bar(0, bar(1,
				   "b"));
	}

	/**
	 * Class Foo
	 * @version 1.0
	 * @Author Ronald vanWoensel <rvw@cosninix.com>
	 */
	abstract class Foo
		extends FooBaseClass
		implements Bar1, Bar2, Bar3 {

		public $numbers = ["one", "two", "three", "four", "five", "six"];
		public $v       = 0; // comment
		public $path    = "root"; // comment

		const FIRST  = 'first';
		const SECOND = 0;
		const Z      = -1;

		public function bar($v,
					 $w = "a") {
			$y = $w;
			$result = foo("arg1",
						  "arg2",
						  10);
			switch ($v) {
			case 0:
				return 1;
			case 1:
				echo '1';
				break;
			case 2:
				echo 'as';
				// fallthrough
			default:
				$result = 10;
			}
			return $result;
		}

		/**
		 * @param $argA
		 * @param $argB
		 * @param $argC
		 * @param $argD
		 * @param $argE
		 * @param $argF
		 * @param $argG
		 * @param $argH
		 */
		public static function fOne($argA,
									$argB,
									$argC,
									$argD,
									$argE, $argF, $argG, $argH) {
			$x = $argA + $argB + $argC + $argD + $argE + $argF + $argG + $argH;
			list($field1, $field2, $field3, $filed4, $field5, $field6) = explode(",", $x);
			fTwo($argA, $argB, $argC, fThree($argD, $argE, $argF, $argG, $argH));
			$z = $argA == "Some string" ? "yes" : "no";
			$colors = ["red", "green", "blue", "black", "white", "gray"];
			$count = count($colors);
			for ($i = 0; $i<$count; $i++) {
				$colorString = $colors[$i];
			}
		}

		public function fTwo($strA, $strB,
					  $strC, $strD) {
			if ($strA == "one" || $strB == "two" || $strC == "three") {
				return $strA + $strB + $strC;
			}
			$x = $foo->one("a", "b")->two("c", "d", "e")->three("fg")->four();
			$y = a()->b()->c();
			return $strD;
		}

		public function fThree($strA, $strB,
						$strC, $strD,
						$strE) {
			try {
			} catch (Exception $e) {
				foo();
			} finally {
				// do something
			}
			return $strA + $strB + $strC + $strD + $strE;
		}

		abstract protected function fFour();

	}
}
