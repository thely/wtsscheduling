## Filterable Pods

Fetches a list of Pods of a given type by specific parameters. The best way to set this up is:

 * Make a regular dropdown field, set it to Auto-Populate, select Taxonomy, select wts_subject. Put "subj_source" as its custom class.
 * Make a Filterable Pods field, and set Course as its Pod type. Put "wts_subject.slug" as its filter, and "subj_source" in "custom class of the field."