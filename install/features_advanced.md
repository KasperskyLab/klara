## Advanced features

# Hash checking

For administrator only, `Advanced Search` page is available, allowing them to check how many KLara jobs matched a particular MD5 or a list of MD5s.

Admins can use this feature to check how many KLara rules generated similar results.


# Virus Collection control file

As outlined in [installation steps](README.md), this file defines whether a specific scan repository (virus collection) repository will be scanned by one KLara Worker.
Furthermore, this file can define some options in order to modify the behavior of Yara scans. These options are:

### `redirect_paths`

This JSON entry needs to be a list of alternative paths that should be scanned *instead* of the actual folder that exists on the disk (currently only the first entry in the config list is take into consideration). For example, if the control file is located at the following absolute path:

`/mnt/storage/vircol/vt_samples/repository_control.txt`

and contains the following JSON entry:

`{"owner": "John Doe", "files_type": "elf", "repository_type": "APT", "redirect_paths": ["/mnt/nas/klara_bigger_collection/"]}`

then KLara will **not** Yara scan directory `/mnt/storage/vircol/vt_samples/`, but instead will start in `/mnt/nas/klara_bigger_collection/`.

NOTE: This is useful if one is using dynamic repositories or is generating repositories on the fly. Also useful for expanding a scan repository into multiple paths. Currently, the expanding feature is not yet implemented, since KLara Workers only fetch the first entry in the config list. But theoretically, one could add multiple folders:

```
redirect_paths": ["/mnt/nas/klara_bigger_collection/", "/mnt/other_nas/other_collection/"]
```

### `results_path_replace_pattern` + `results_path_replace_with`

When returning results from a KLara scan, entire scan paths are being displayed in the results text box. For example, when a Worker accepts running a KLara job for `/vt_samples`, and has `/mnt/storage/vircol/` set as `virus_collection` in config, the following results might be returned:

```
apt_ZZ_unknown_apt /mnt/storage/vircol/vt_samples/1.exe
apt_ZZ_unknown_apt /mnt/storage/vircol/vt_samples/2.bin
apt_ZZ_unknown_apt /mnt/storage/vircol/vt_samples/3.dll
```
Where:

* `apt_ZZ_unknown_rule` is the rule name
* `/mnt/storage/vircol/` is the `virus_collection` variable set in Worker config
* `/vt_samples` is the scan repository selected in the web interface when submitting the KLara job
* `{1.exe, 2.bin, 3.dll}` are various matched files in that specific scan repository (`/vt_samples`)

Sometimes you don't want showing your web users the entire scan path, and this is when `results_path_replace_pattern` comes in handy. Basically, one can define a `re.sub` pattern that should replace the absolute path of scan repository with anything else defined in `results_path_replace_with`.

`results_path_replace_pattern` should not include the entire scan repository because this gets prefixed automatically. **Note**: be careful with trailing slashes.

Based on the above example, here are some pattern examples:


| `results_path_replace_pattern` | Generated `re.sub` pattern | Explanation |
| ------------------------------ | ---------------------      | ----------- |
| "`.*/`"	   	| `/mnt/storage/vircol/vt_samples.*/`         | Matches the entire path, **leaving** file name intact |
| "`/test/*`"	| `/mnt/storage/vircol/vt_samples/test/*`     | Matches a directory called /test/ in the scan repository, everything else won't be changed |
| "`/`"     	| `/mnt/storage/vircol/vt_samples/`           | Matches the prefix from `virus_collection`, up to scan repository
| "" (empty string)| `/mnt/storage/vircol/vt_samples`         | Same as above (notice the missing `"/"`)
| "`/file.exe`" | `/mnt/storage/vircol/vt_samples/file.exe`   | Replaces the full path of a file

As such, if Virus Collection control file contains:

```
{
	"owner": "John Doe",
	"files_type": "mixed",
	"repository_type": "APT",
	"results_path_replace_pattern": "/",
	"results_path_replace_with": "[KLara repository] => "
}
```

then running a KLara job over `/mnt/storage/vircol/` can return the following results (based on the above example):

```
apt_ZZ_unknown_rule [KLara repository] => 1.exe
apt_ZZ_unknown_rule [KLara repository] => 2.bin
apt_ZZ_unknown_rule [KLara repository] => 3.dll
```


NOTE: `redirect_paths` has precedence over `results_path_replace_pattern` and it can be only one `results_path_replace_pattern` defined per repository

For further info about how these settings work, please check [Worker's source code](https://github.com/KasperskyLab/klara/blob/master/worker/klara-worker#L120)


# HTTP REST API

KLara provides a powerful API, allowing automating most web actions. Each user can be assigned an API key, allowed to access specific API endpoints (JSON setting defined in `api_perms` from `users` table) and this allows creating / viewing / deleting jobs automatically.
API Spec will be released soon.


