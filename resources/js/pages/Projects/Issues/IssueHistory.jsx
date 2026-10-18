import { dateTime, diffForHumans } from '@/utils/datetime';
import { Avatar, Center, Group, Loader, Stack, Text, Timeline, Tooltip } from '@mantine/core';
import axios from 'axios';
import { useEffect, useState } from 'react';

const describeEvent = entry => {
  if (entry.event === 'created') return 'created the issue';
  if (entry.event === 'archived') return 'archived the issue';
  if (entry.event === 'restored') return 'restored the issue';
  return 'made changes';
};

function Change({ change }) {
  if (change.long) {
    return (
      <Text size='sm'>
        <Text
          span
          fw={600}
        >
          {change.field}
        </Text>{' '}
        was edited
      </Text>
    );
  }

  return (
    <Text size='sm'>
      <Text
        span
        fw={600}
      >
        {change.field}
      </Text>
      {': '}
      <Text
        span
        c='dimmed'
        td={change.from ? 'line-through' : undefined}
      >
        {change.from ?? 'empty'}
      </Text>
      {' → '}
      <Text span>{change.to ?? 'empty'}</Text>
    </Text>
  );
}

/**
 * Every change made to the issue, newest first.
 */
export default function IssueHistory({ issue }) {
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get(route('projects.issues.history', [issue.project_id, issue.id]))
      .then(({ data }) => setEntries(data))
      .catch(() => alert('Failed to load history'))
      .finally(() => setLoading(false));
  }, [issue.id, issue.updated_at]);

  if (loading) {
    return (
      <Center mih={100}>
        <Loader />
      </Center>
    );
  }

  if (entries.length === 0) {
    return (
      <Text
        c='dimmed'
        size='sm'
        ta='center'
        mt='xl'
      >
        No history yet
      </Text>
    );
  }

  return (
    <Timeline
      bulletSize={28}
      lineWidth={2}
      mt='md'
    >
      {entries.map(entry => (
        <Timeline.Item
          key={entry.id}
          bullet={
            <Avatar
              src={entry.user?.avatar}
              radius='xl'
              size={26}
            />
          }
          title={
            <Group gap={6}>
              <Text
                size='sm'
                fw={600}
              >
                {entry.user?.name ?? 'System'}
              </Text>
              <Text size='sm'>{describeEvent(entry)}</Text>
              <Tooltip
                label={dateTime(entry.created_at)}
                openDelay={250}
                withArrow
              >
                <Text
                  size='xs'
                  c='dimmed'
                >
                  {diffForHumans(entry.created_at)}
                </Text>
              </Tooltip>
            </Group>
          }
        >
          <Stack
            gap={2}
            mt={4}
          >
            {entry.changes.map(change => (
              <Change
                key={change.field}
                change={change}
              />
            ))}
          </Stack>
        </Timeline.Item>
      ))}
    </Timeline>
  );
}
