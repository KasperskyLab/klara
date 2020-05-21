## Filesystem optimisations

In order to efficiently run Klara, a fast enough file system needs to be deployed. From our experience, we recommend running Klara on a machine that has file repositories stored on multiple SSDs, preferably running in a RAID configuration.

With 8 Samsung SSDs, configured in RAID5 we have observed a performance boost of 8x times, achieving a total scan speed of ~3 GB/s. Furthermore, using the same configuration in RAID0 we have observed a performance increase of up to 20%, achieving almost 3.5 GB/s. The main difference between RAID0 and RAID5 is if a single disk fails, the entire array is lost (vs RAID5, which is resilient to a loss of 1 disk. RAID6 is resilient to a loss of max 2 disks)

Secondly, using a fast enough file system helps a lot. In our experience, we recommend using [XFS filesystem](https://en.wikipedia.org/wiki/XFS). ReiserFS also achieved some degree of performance. We have used the following options when creating the filesystem consisting of 8x SSD drives in a RAID5 config (1 drive is used for parity check)


```
mkfs.xfs -f -d su=256k,sw=7,agcount=24 -l version=2,su=256 -i size=512 <target_device>
```

as well as these options for mounting the filesystem:

```
mount -o noatime,nodiratime,nobarrier,largeio,inode64,swalloc,logbufs=8,logbsize=256k,allocsize=131072k <target_device> <target_dir>

