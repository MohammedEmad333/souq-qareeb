# Theme record backup and restore

This is a manual recovery utility for the existing WordPress `wp_template_part`, `wp_navigation` and `wp_global_styles` records. **It does not deploy versioned theme changes.**

```bash
cd ~/souq-qareeb/source
bash scripts/theme-records.sh --backup
# Inspect and record the printed backup path.
# Only after explicitly approving a restore:
bash scripts/theme-records.sh --restore /home/ubuntu/souq-qareeb/deploy-backups/theme-records-XXXXXXXX
```

Backups are saved outside the repository and should never be committed. The restore command requires typing `RESTORE`, and overwrites exactly three matching records selected by post type and slug. Back up again immediately before restore, because the live WordPress editor may have changed.

**Limitations:** Restoration is not atomic; it does not restore uploaded media, site options, other templates, theme files or the whole database. A failed partial restore may require manual recovery. Before restoring production, review backup content and run a separate WordPress database backup. Test front-end navigation and page rendering afterward. No changes to EduBridge or other containers.
