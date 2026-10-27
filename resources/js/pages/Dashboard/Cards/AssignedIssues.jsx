import Card from '@/components/Card';
import EmptyWithIcon from '@/components/EmptyWithIcon';
import { date, diffForHumans } from '@/utils/datetime';
import { issueCode, issueSeverityColors, issueStatusColors } from '@/utils/issue';
import { redirectTo } from '@/utils/route';
import { Badge, Box, Center, Divider, Group, ScrollArea, Stack, Text, Title, Tooltip } from '@mantine/core';
import { IconBug } from '@tabler/icons-react';
import classes from './css/RecentlyAssignedTasks.module.css';

const label = value => value.replace('_', ' ');

export default function AssignedIssues({ issues }) {
  return (
    <Card bg='none'>
      <Title
        order={3}
        ml={15}
      >
        Issues assigned to you
      </Title>

      <Divider my={14} />

      {issues.length > 0 ? (
        <ScrollArea
          h={300}
          scrollbarSize={7}
        >
          <Stack gap={10}>
            {issues.map(issue => (
              <Box
                key={issue.id}
                className={classes.task}
              >
                <Group
                  wrap='nowrap'
                  justify='space-between'
                >
                  <Stack gap={3}>
                    <Text
                      fz={13}
                      fw={600}
                      onClick={() => redirectTo('projects.issues.open', [issue.project_id, issue.id])}
                      className={classes.link}
                    >
                      {issueCode(issue)}: {issue.title}
                    </Text>
                    <Group gap='xs'>
                      <Badge
                        size='xs'
                        variant='light'
                        color={issueStatusColors[issue.status]}
                      >
                        {label(issue.status)}
                      </Badge>
                      {issue.severity && (
                        <Badge
                          size='xs'
                          variant='outline'
                          color={issueSeverityColors[issue.severity]}
                        >
                          {issue.severity}
                        </Badge>
                      )}
                      <Text
                        fz={11}
                        c='dimmed'
                      >
                        {issue.project.name}
                      </Text>
                    </Group>
                  </Stack>
                  <Tooltip
                    label={date(issue.created_at)}
                    openDelay={500}
                    withArrow
                  >
                    <Text
                      fz={11}
                      fw={700}
                      className={classes.due}
                    >
                      {diffForHumans(issue.created_at)}
                    </Text>
                  </Tooltip>
                </Group>
              </Box>
            ))}
          </Stack>
        </ScrollArea>
      ) : (
        <Center my={30}>
          <EmptyWithIcon
            title='No issues'
            subtitle='You have no open issues assigned to you'
            icon={IconBug}
          />
        </Center>
      )}
    </Card>
  );
}
