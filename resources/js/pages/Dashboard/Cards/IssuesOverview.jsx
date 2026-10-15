import Card from '@/components/Card';
import { issueSeverityColors, issueStatusColors } from '@/utils/issue';
import { redirectTo } from '@/utils/route';
import { Anchor, Box, ColorSwatch, Divider, Group, Progress, Stack, Text, Title, Tooltip } from '@mantine/core';

function CountList({ rows, colors, total }) {
  return (
    <Stack gap={10}>
      {rows.map(row => {
        const percent = total > 0 ? (row.count / total) * 100 : 0;

        return (
          <Tooltip
            key={row.value}
            label={`${row.label}: ${row.count} (${Math.round(percent)}%)`}
            openDelay={300}
            withArrow
          >
            <Box>
              <Group
                justify='space-between'
                mb={4}
              >
                <Group gap={8}>
                  <ColorSwatch
                    color={`var(--mantine-color-${colors[row.value]}-6)`}
                    size={10}
                    withShadow={false}
                  />
                  <Text fz='sm'>{row.label}</Text>
                </Group>
                <Text
                  fz='sm'
                  fw={600}
                >
                  {row.count}
                </Text>
              </Group>
              <Progress
                value={percent}
                color={colors[row.value]}
                size={4}
                radius='xl'
              />
            </Box>
          </Tooltip>
        );
      })}
    </Stack>
  );
}

export default function IssuesOverview({ stats }) {
  const openSeverityTotal = stats.openBySeverity.reduce((sum, row) => sum + row.count, 0);

  return (
    <Card bg='none'>
      <Group
        justify='space-between'
        mx={15}
      >
        <Title order={3}>Issues overview</Title>
        <Anchor
          fz='sm'
          onClick={() => redirectTo('issues.index')}
        >
          View all
        </Anchor>
      </Group>

      <Group
        gap='xl'
        mx={15}
        mt='md'
      >
        <div>
          <Text
            fz={28}
            fw={700}
            lh={1}
          >
            {stats.open}
          </Text>
          <Text
            fz='xs'
            c='dimmed'
          >
            Open issues
          </Text>
        </div>
        <div>
          <Text
            fz={28}
            fw={700}
            lh={1}
          >
            {stats.total}
          </Text>
          <Text
            fz='xs'
            c='dimmed'
          >
            All issues
          </Text>
        </div>
      </Group>

      <Divider my={14} />

      <Box mx={15}>
        <Text
          fz='xs'
          fw={700}
          tt='uppercase'
          c='dimmed'
          mb='sm'
        >
          By status
        </Text>
        <CountList
          rows={stats.byStatus}
          colors={issueStatusColors}
          total={stats.total}
        />

        <Text
          fz='xs'
          fw={700}
          tt='uppercase'
          c='dimmed'
          mt='lg'
          mb='sm'
        >
          Open issues by severity
        </Text>
        <CountList
          rows={stats.openBySeverity}
          colors={issueSeverityColors}
          total={openSeverityTotal}
        />
      </Box>
    </Card>
  );
}
