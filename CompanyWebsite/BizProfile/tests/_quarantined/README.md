# Quarantined legacy tests (M0 maintenance)

These files were moved here during M0 local closure because they contain PHP
syntax errors and cannot be parsed/executed. They are quarantined, not deleted,
so they can be reviewed/repaired in a separately approved change.

Original locations:
- tests/Unit/CoreDatabaseTest.php  -> syntax error: `public function function testInsertUser():` (double `function` keyword)
- tests/Integration/ApiIntegrationTest.php -> syntax error: `public function xxx():` (return-type colon with no declared type, on 6 test methods)

Disposition per `docs/ProjectDoc/02_Phase1_Work_Order_M0_M1_v0.1.md` section 0,
item 7: "Repair or quarantine broken legacy test files under a separately
recorded M0 maintenance change." Quarantined pending repair; not executed as
part of M0 smoke tests. Full test execution (PHPUnit + DB) is out of M0 scope.
