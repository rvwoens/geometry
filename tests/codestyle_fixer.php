<?php
declare(strict_types=1);

class Foo
{
	public function foo($x, $z, $obj, $one)
	{
		global $k, $s1, $y;
		$obj->foo()->bar();
		$arr = array(0 => 'zero', 1 => 'one');
		call_func(function () {
			return 0;
		});
		for ($i = 0; $i < $x; $i++) {
			$y += ($y ^ 0x123) << 2;
		}
		$k = $x > 15 ? 1 : 2;
		$k = $x ?: 0;
		$k = $x ?? $z;
		$k = $x <=> $z;
		do {
			try {
				if (!0 > $x && !$x < 10) {
					while ($x != $y) {
						$x = f($x * 3 + 5);
					}
					$z += 2;
				} elseif ($x > 20) {
					$z = $x << 1;
				} else {
					$z = $x | 2;
				}

				$j = (int) $z;
				switch ($j) {
					case 0:
					$s1 = 'zero';
					break;
				case 2:
					$s1 = 'two';
					break;
				default:
					$s1 = 'other';
				}
			} catch (exception $e) {
				$t = $one[0];
				$u = $one['str'];
				$v = $one[$x[1]];
				$cell = $one['cell'];
				$a = $one['a'];
				echo $val{'foo'.$num}[$cell{$a}];
			} finally {
				// do something
			}
		} while ($x < 0);
	}
}

function bar(): Foo
{
}

//
?>
<div><?=foo()?></div>
