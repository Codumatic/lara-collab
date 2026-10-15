import TableRowEmpty from '@/components/TableRowEmpty';
import { diffForHumans } from '@/utils/datetime';
import {
  issueCode,
  issuePriorityColors,
  issueSeverityColors,
  issueStatusColors,
} from '@/utils/issue';
import { Table, Text, Tooltip } from '@mantine/core';
import IssueBadge from '../Projects/Issues/IssueBadge';

/**
 * Issues table shared by the project, all-issues and My Work pages.
 * `renderActions` adds a last column with per-row actions when given.
 */
export default function IssuesTable({
  issues,
  dropdowns,
  onRowClick,
  showProject = false,
  renderActions = null,
}) {
  const columnCount = 8 + (showProject ? 1 : 0) + (renderActions ? 1 : 0);

  const rows = issues.length ? (
    issues.map(issue => (
      <Table.Tr
        key={issue.id}
        style={{ cursor: 'pointer' }}
        onClick={() => onRowClick(issue)}
      >
        <Table.Td w={90}>
          <Text
            size='sm'
            fw={600}
            c='dimmed'
          >
            {issueCode(issue)}
          </Text>
        </Table.Td>
        {showProject && (
          <Table.Td>
            <Text size='sm'>{issue.project?.name}</Text>
          </Table.Td>
        )}
        <Table.Td>
          <Text
            size='sm'
            fw={500}
            lineClamp={1}
          >
            {issue.title}
          </Text>
        </Table.Td>
        <Table.Td>
          <IssueBadge
            value={issue.status}
            options={dropdowns.statuses}
            colors={issueStatusColors}
          />
        </Table.Td>
        <Table.Td>
          <IssueBadge
            value={issue.severity}
            options={dropdowns.severities}
            colors={issueSeverityColors}
          />
        </Table.Td>
        <Table.Td>
          <IssueBadge
            value={issue.priority}
            options={dropdowns.priorities}
            colors={issuePriorityColors}
          />
        </Table.Td>
        <Table.Td>
          <Text size='sm'>{issue.assigned_to_user?.name ?? '—'}</Text>
        </Table.Td>
        <Table.Td>
          <Text size='sm'>{issue.reported_by_user?.name ?? '—'}</Text>
        </Table.Td>
        <Table.Td>
          <Tooltip
            label={new Date(issue.created_at).toLocaleString()}
            openDelay={500}
            withArrow
          >
            <Text
              size='sm'
              c='dimmed'
            >
              {diffForHumans(issue.created_at)}
            </Text>
          </Tooltip>
        </Table.Td>
        {renderActions && <Table.Td w={50}>{renderActions(issue)}</Table.Td>}
      </Table.Tr>
    ))
  ) : (
    <TableRowEmpty colSpan={columnCount} />
  );

  return (
    <Table.ScrollContainer minWidth={900}>
      <Table
        verticalSpacing='sm'
        highlightOnHover
      >
        <Table.Thead>
          <Table.Tr>
            <Table.Th>ID</Table.Th>
            {showProject && <Table.Th>Project</Table.Th>}
            <Table.Th>Title</Table.Th>
            <Table.Th>Status</Table.Th>
            <Table.Th>Severity</Table.Th>
            <Table.Th>Priority</Table.Th>
            <Table.Th>Assignee</Table.Th>
            <Table.Th>Reporter</Table.Th>
            <Table.Th>Created</Table.Th>
            {renderActions && <Table.Th />}
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>{rows}</Table.Tbody>
      </Table>
    </Table.ScrollContainer>
  );
}
