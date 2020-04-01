CHANGELOG
==========================

## 1.1.1 (`1010170`)

- **Fix:** n+1 behavior when reporting message (#15)

## 1.1.0 (`1010070`)

- **New:** Allow providing more information even with report reason is selected (#11)
  - Adds a new default report reason "Other" on both fresh installation and when upgrading to this version
     - This report reason cannot be disabled, deleted or exported

## 1.0.0 (`1000070`)

- **New:** Allow exporting/importing report reasons (#9)

## 1.0.0 Alpha 3 (`1000013`)

- **New:** Allow changing display order of report reasons (#5)
- **New:** Allow toggling report reasons visibility in public (#6)
- **Change:** Update execution order to match with resource id on XenForo.com (#7)
- **Fix:** Report queue relation is added to report reason entity structure even if Report Centre Essentials is not installed or enabled (#4)

## 1.0.0 Alpha 2 (`1000012`)

- **New:** Allow selecting report queue to create report in if @Xon's Report Centre Essentials is installed (#2)

## 1.0.0 Alpha 1 (`1000011`)

Initial release