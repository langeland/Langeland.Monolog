Monolog Integration for Flow
============================

Provides a monolog factory ready for Flow 5.0.
Also replaces all Neos logs (Security, System, Query, I18n) with Monolog by default.
To change that and the handlers check the Settings.yaml.

For more information about Handlers check also the monolog package.
 
> The monolog format is slightly different than the default Flow log file format, 
> also the configured monolog handler does no log rotation like the Flow log does, 
> so you need to take care of that.
