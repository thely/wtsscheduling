# Pods Data Field

Selects pods based on a provided query and sets the value of the field
to a JSON-encoded representation of the retrieved data. One or multiple
fields can be retrieved from the matched pods.

The `Pod` field specifies the Pod to search.

The `Where query` option can be given any value that would return the
desired pods.

The `Pod field to return` option can be given a string that matches the
field slug for a pod property, in which case an array containing the values
for each matched slug is returned, or a comma-separated list of properties,
in which case an array of objects with keys corresponding to the property
names and values corresponding to the retrieved values, would be returned.
