# HOTKEYS command specification

## Command description

Starts/stops tracking of hotkeys in a redis-server instance and reports top K such keys.
Hotkeys are measured by two metrics - cpu execution time and total bytes read/written from the network.
Reporting returns meaningful results only during or after stopping tracking.

## Command API
```
HOTKEYS START	<METRICS count [CPU] [NET]> [COUNT k] [DURATION duration] [SAMPLE ratio] [SLOTS count slot…]
HOTKEYS STOP
HOTKEYS RESET
HOTKEYS GET
```

Options:
- GET - Return requested(via START METRICS) lists of top K hotkeys - one by cpu time and one by network bytes
  (returns raw data, i.e used for cpu and num bytes for net).
  If no tracking is started or tracking was reset returns (nil)
- START - starts hotkeys tracking. Overwrite previous session if one is already started.
  Return error if a session is already started.
- METRICS - choose which metrics to track.
  Currently supported options CPU (i.e cpu time spend on the key) and NET (sum of ingress/egress/replication network bytes used by the key)
- Argument COUNT sets how many keys we need to report. Default: 10, min: 10, max: 64
- Argument DURATION - instead of calling STOP, automatically stop the tracking after stop-after amount of seconds. Default: 0(do not stop automatically), min: 0, max: unlimited
- Argument SAMPLE - log a key inside the top K structure with probability 1/ratio. Default: 1(i.e track every key). Min: 1, max: INT_MAX
- Argument SLOTS - only track keys if they are from the specified slots
- STOP - stops tracking but results are not released, so GET can return them.
- RESET - Release the resources used for hotkey tracking. Return error if a session is active (i.e it must be stopped before reset, either via HOTKEYS STOP or auto-stopped via START’s DURATION param).


## Test plan

Specify how do you want to test the command in terms of integration testing:

- Test HOTKEYS START only with CPU metric, verify that corresponding key returned by HOTKEYS GET command
- Test HOTKEYS START only with NET metric, verify that corresponding key returned by HOTKEYS GET command
- Test HOTKEYS START with both CPU and NET metrics, verify that corresponding keys returned by HOTKEYS GET command
- Test with COUNT option, verify that correct number of keys returned by HOTKEYS GET command
- Test with DURATION option, verify that tracking stops after specified duration
- Test with SAMPLE option, verify that correct number of keys returned by HOTKEYS GET command
- Test with SLOTS option, verify that correct keys returned by HOTKEYS GET command
