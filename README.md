# geometry
[![GitHub Release](https://img.shields.io/github/v/tag/rvwoens/geometry.svg?style=flat)](//packagist.org/packages/rvwoens/geometry)
[![GitHub Release](https://img.shields.io/packagist/v/rvwoens/geometry.svg?style=flat)](//packagist.org/packages/rvwoens/geometry)
[![Total Downloads](https://poser.pugx.org/rvwoens/geometry/downloads)](//packagist.org/packages/rvwoens/geometry)
[![License](https://poser.pugx.org/rvwoens/geometry/license)](//packagist.org/packages/rvwoens/geometry)
[![Actions Status](https://github.com/rvwoens/geometry/workflows/CI/badge.svg)](https://github.com/rvwoens/geometry/actions)

Geometry 2D shapes helper functions

### Coord
A coord is a simple latitude / longitude coordinate class with some calculation and manipulation methods
```
// instantiation
$coord = new Coord(52.40010210009, 4.5776320002);

// properties
$lat=$coord->latitude;                  // latitude property
$lng=$coord->longitude;                 // longitude property

// methods
round(5)                                - round lat/long to a precision (default 6 = approx 10 cm)
movedClone(50, 180)                     - make a clone and move it 50 meter southwards (bearing 180)
toString()                              - "52.400102,4.577632"  6 digits significant (approx 10 cm)
toWktString()                           - "4.577632 52.400102"  WKT (Well Known Text format)
equals($coord2)                         - a coord is equal when within a millimeter of another coord 
distance($coord3)                       - calculate distance between coords in meters
bearing($coordTo)                       - Bearing (degrees) between 2 coords (vector TOWARDS coordTo, 0=north)
move($distance,$bearing);               - move of coord (use movedclone for immutable variant)
isRDcoord()                             - true if the coord is a RD (dutch: Rijksdriehoeksmeting) coordinate
makeWGS84fromRD()                       - Factory to create a converted Wgs84 coordinate from a RD coordiante
```

### Polygon
A polygon is a number of locations on a map defining an area. 
```
// instantiation. The last segment is automatically closed using the first coordinate
$polygon = new Polygon( [ [52.1,4.2], [52.2,4.2], [52.2,4.3] ]);                // define with simple lat/lng array
$polygon = new Polygon( [   ['lat'=>52.1,'lng'=>4.2], 
                            ['lat'=>52.2,'lng'=>4.2], 
                            ['lat'=>52.2,'lng'=>4.3] ]);                        // or using this format
$c1=new Coord(52.1,4.2);
$c2=new Coord(52.2,4.2);
$c3=new Coord(52.2,4.3);
$polygon = new Polygon( [ $c1,$c2,$c3 ]);                                       // or using an array of Coords
$polygon = new Polygon("POLYGON ((4.2 52.1, 52.2 4.2, 4.3 52.2, 4.2 52.1))");   // or using a WKT definition
$polygon = new Polygon("52.1,4.2|52.2,4.2|52.2,4.3");                           // or using the internal serialisation 

// methods
valid()                         - a polygon is valid when it is at least a triangle with 3 valid Coords
size()                          - number of sides of the polygon or n of the n-gon.
polyString()                    - to internal serialisation string "52.1,4.2|52.2,4.2|52.2,4.3"
polyWktString()                 - to WKT definition "POLYGON ((4.2 52.1, 52.2 4.2, 4.3 52.2, 4.2 52.1))"
polyGeoJsonArray()              - convert to Geojson but in php array format
polyGeoJsonString()             - convert to Geojson json string
polyLatLngArray()               - convert to array format [ ['lat'=>lat,'lng'=>lng]...]
equals($polyon2)                - true when each node is within 1mm of the other
farAway($coord)                 - true when polygon is "far away" from the coord (fast!) to speed up some calculations
distance($coord | $polygon)     - closest distance to a point or another polygon
round($precision)               - round each node of the polygon to a certain precision (default 6 approx 10cm)

contains($coord)                - true when Coord is inside the polygon
areaSquareMeters()              - returns area of polygon in square meters using earth projection
center()                        - calculate the center of mass of the polygon as a Coord

// note: smallest outer circle and largest inner circle are simplifications. Both use polygon center
smallestOuterCircleRadius()     - calculate the smallest outer circle (simple version)
largestInnerCircleRadius()      - calculate the smallest inner circle (simple version)

// Each method creates a clone. Polygon is immutable 
expand(10)                      - create an expanded (inflated) polygon by x meters (negative values will deflate the polygon)
movedClone($distance,$bearing)  - create a moved copy of the polygon
simplify($distance,$highQuality)- create a simplified polygon by removing coordinates but keeping shape
makeCombined(Polygon $add)      - add a polygon, create a connection between both (for converting multipolygons)
```




