import { copy } from 'angular';
import _ from 'lodash';
import moment from 'moment';

export default TuleapArtifactFieldValuesService;

TuleapArtifactFieldValuesService.$inject = [
    '$sce',
    'TuleapArtifactModalAwkwardCreationFields'
];

function TuleapArtifactFieldValuesService(
    $sce,
    TuleapArtifactModalAwkwardCreationFields
) {
    var self = this;
    self.getSelectedValues = getSelectedValues;

    /**
     * For every field in the tracker, creates a field object with the value from the given artifact
     * or the field's default value if there is no artifact and there is a default value.
     * @param  {Array} artifact_values            A map of artifact values from the edited artifact field_id: { field_id, value|bind_value_ids } OR an empty object
     * @param  {TrackerRepresentation} tracker    The tracker as returned from the REST route
     * @return {Object}                           A map of objects indexed by field_id => { field_id, value|bind_value_ids }
     */
    function getSelectedValues(artifact_values, tracker) {
        var values = {};
        var artifact_value;

        _.forEach(tracker.fields, function(field) {
            artifact_value = artifact_values[field.field_id];

            if (_(TuleapArtifactModalAwkwardCreationFields).contains(field.type)) {
                values[field.field_id] = {
                    field_id: field.field_id,
                    type    : field.type
                };
            } else if (artifact_value) {
                values[field.field_id] = formatExistingValue(field, artifact_value);
            } else {
                values[field.field_id] = getDefaultValue(field);
            }
        });

        return values;
    }

    function formatExistingValue(field, artifact_value) {
        var value_obj         = copy(artifact_value);
        value_obj.type        = field.type;
        value_obj.permissions = field.permissions;

        switch (field.type) {
            case 'date':
                if (field.is_time_displayed) {
                    if (artifact_value.value) {
                        value_obj.value = moment(artifact_value.value, moment.ISO_8601).format('YYYY-MM-DD HH:mm:ss');
                    }
                } else {
                    if (artifact_value.value) {
                        value_obj.value = moment(artifact_value.value, moment.ISO_8601).format('YYYY-MM-DD');
                    }
                }
                break;
            case 'cb':
                value_obj.bind_value_ids = mapCheckboxValues(field, artifact_value.bind_value_ids);
                break;
            case 'sb':
            case 'msb':
            case 'rb':
                value_obj.bind_value_ids = ! _.isEmpty(artifact_value.bind_value_ids) ? artifact_value.bind_value_ids : [100];
                break;
            case 'text':
                value_obj.value = {
                    content: artifact_value.value,
                    format : artifact_value.format
                };
                delete value_obj.format;
                break;
            case 'perm':
                value_obj.value = {
                    is_used_by_default: field.values.is_used_by_default,
                    granted_groups    : artifact_value.granted_groups
                };
                delete value_obj.granted_groups;
                break;
            case 'tbl':
                value_obj.value = {
                    bind_value_objects: _.uniq(artifact_value.bind_value_objects, function(item) {
                        if (item.is_anonymous) {
                            return item.email;
                        }
                        return item.id;
                    })
                };
                delete value_obj.bind_value_objects;
                delete value_obj.bind_value_ids;
                break;
            case 'file':
                value_obj       = addTemporaryFileToValue(value_obj);
                value_obj.value = _.pluck(artifact_value.file_descriptions, 'id');
                break;
            case 'computed':
                delete value_obj.value;
                break;
            default:
                break;
        }

        return value_obj;
    }

    function getDefaultValue(field) {
        var value_obj = {
            field_id   : field.field_id,
            type       : field.type,
            permissions: field.permissions
        };
        var default_value;
        switch (field.type) {
            case 'sb':
                default_value = _.pluck(field.default_value, 'id');
                if (field.has_transitions) {
                    // the default value may not be a valid transition value
                    if (field.default_value && defaultValueExistsInValues(field.values, default_value)) {
                        value_obj.bind_value_ids = [].concat(default_value);
                    } else if (field.values[0]) {
                        value_obj.bind_value_ids = [].concat(field.values[0].id);
                    }
                } else {
                    value_obj.bind_value_ids = (! _.isEmpty(default_value)) ? [].concat(default_value) : [100];
                }
                break;
            case 'msb':
                default_value = _.pluck(field.default_value, 'id');
                value_obj.bind_value_ids = (! _.isEmpty(default_value)) ? [].concat(default_value) : [100];
                break;
            case 'cb':
                default_value = _.pluck(field.default_value, 'id');
                value_obj.bind_value_ids = mapCheckboxValues(field, default_value);
                break;
            case 'rb':
                default_value = _.pluck(field.default_value, 'id');
                value_obj.bind_value_ids = (! _.isEmpty(default_value)) ? default_value : [100];
                break;
            case 'int':
                value_obj.value = (field.default_value) ? parseInt(field.default_value, 10) : null;
                break;
            case 'float':
                value_obj.value = (field.default_value) ? parseFloat(field.default_value, 10) : null;
                break;
            case 'text':
                value_obj.value  = {
                    content: null,
                    format: 'text'
                };
                if (field.default_value) {
                    value_obj.value.format  = field.default_value.format;
                    value_obj.value.content = field.default_value.content;
                }
                break;
            case 'string':
            case 'date':
                value_obj.value = (field.default_value) ? field.default_value : null;
                break;
            case 'art_link':
                value_obj.unformatted_links = '';
                value_obj.links = [{ id: '' }];
                break;
            case 'staticrichtext':
                value_obj.default_value = $sce.trustAsHtml(field.default_value);
                break;
            case 'perm':
                value_obj.value = {
                    is_used_by_default: field.values.is_used_by_default,
                    granted_groups    : []
                };
                break;
            case 'tbl':
                value_obj.value = {
                    bind_value_objects: (field.default_value) ? [].concat(field.default_value) : []
                };
                break;
            case 'file':
                value_obj       = addTemporaryFileToValue(value_obj);
                value_obj.value = [];
                break;
            case 'computed':
                value_obj.is_autocomputed = true;
                value_obj.manual_value    = null;
                break;
            default:
                // Do nothing
                break;
        }
        return value_obj;
    }

    function addTemporaryFileToValue(value_obj) {
        value_obj.temporary_files = [
            {
                file: {},
                description: ""
            }
        ];
        return value_obj;
    }

    function defaultValueExistsInValues(values, default_value_id) {
        var found = _.find(values, function(val) {
            return val.id === default_value_id;
        });
        return found !== undefined;
    }

    function mapCheckboxValues(field, expected_values) {
        return _.map(field.values, function(possible_value) {
            return (_.contains(expected_values, possible_value.id)) ? possible_value.id : null;
        });
    }
}
