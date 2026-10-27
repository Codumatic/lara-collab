import { optionLabel } from '@/utils/issue';
import { Badge, Text } from '@mantine/core';

export default function IssueBadge({ value, options, colors }) {
  if (!value) {
    return (
      <Text
        size='sm'
        c='dimmed'
      >
        —
      </Text>
    );
  }

  return (
    <Badge
      color={colors[value] ?? 'gray'}
      variant='light'
      radius='sm'
    >
      {optionLabel(options, value)}
    </Badge>
  );
}
