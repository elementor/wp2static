<?php
namespace Dropbox;

/**
 * @internal
 */
final class SdkVersion {
    // When doing a release:
    // 1. Set VERSION to the version you're about to release, tag the relase, then commit+push.
    // 2. Immediately afterwards, append "-dev", then commit+push.
    const VERSION = "1.1.6";
}
