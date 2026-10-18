import SearchInput from '@/components/SearchInput';
import { currentUrlParams, reloadWithQuery, reloadWithoutQueryParams } from '@/utils/route';
import { Button, Group, MultiSelect } from '@mantine/core';
import { IconFilterOff } from '@tabler/icons-react';

const FILTER_KEYS = [
  'search',
  'statuses',
  'severities',
  'priorities',
  'assignees',
  'reporters',
  'labels',
];

const asStrings = values => (Array.isArray(values) ? values.map(String) : []);

const userOptions = users => users.map(user => ({ value: user.id.toString(), label: user.name }));

/**
 * Search and filter controls for the issue lists. Every change reloads the page with the
 * filters in the URL, so the backend does the filtering and the link can be shared.
 */
export default function IssueFilters({
  dropdowns,
  assignees = [],
  reporters = [],
  labels = [],
  showAssigneeFilter = true,
}) {
  const params = currentUrlParams();
  const hasActiveFilters = FILTER_KEYS.some(key =>
    Array.isArray(params[key]) ? params[key].length > 0 : !!params[key]
  );

  const applyFilter = (key, values) => {
    if (values.length) {
      reloadWithQuery({ [key]: values, page: undefined }, true);
    } else {
      reloadWithoutQueryParams({ exclude: [key, 'page'] });
    }
  };

  const filters = [
    { key: 'statuses', placeholder: 'Status', data: dropdowns.statuses },
    { key: 'severities', placeholder: 'Severity', data: dropdowns.severities },
    { key: 'priorities', placeholder: 'Priority', data: dropdowns.priorities },
    showAssigneeFilter && {
      key: 'assignees',
      placeholder: 'Assignee',
      data: userOptions(assignees),
    },
    { key: 'reporters', placeholder: 'Reporter', data: userOptions(reporters) },
    {
      key: 'labels',
      placeholder: 'Labels',
      data: labels.map(label => ({ value: label.id.toString(), label: label.name })),
    },
  ].filter(Boolean);

  return (
    <Group
      gap='xs'
      mb='lg'
      align='flex-start'
    >
      <SearchInput
        placeholder='Search title or ISS-number'
        search={search => reloadWithQuery({ search, page: undefined }, true)}
        w={260}
      />

      {filters.map(filter => {
        const selected = asStrings(params[filter.key]);

        return (
          <MultiSelect
            key={filter.key}
            placeholder={selected.length ? undefined : filter.placeholder}
            aria-label={filter.placeholder}
            data={filter.data}
            value={selected}
            onChange={values => applyFilter(filter.key, values)}
            searchable
            clearable
            radius='xl'
            w={selected.length ? 'auto' : 140}
            miw={140}
            maw={320}
            comboboxProps={{ withinPortal: true }}
          />
        );
      })}

      {hasActiveFilters && (
        <Button
          variant='subtle'
          color='gray'
          radius='xl'
          leftSection={<IconFilterOff size={14} />}
          onClick={() => reloadWithoutQueryParams({ exclude: [...FILTER_KEYS, 'page'] })}
        >
          Clear filters
        </Button>
      )}
    </Group>
  );
}
