import {
  ActionIcon,
  Button,
  Group,
  Paper,
  Stack,
  Text,
  Textarea,
  Tooltip,
  rem,
} from '@mantine/core';
import { IconArrowDown, IconArrowUp, IconPlus, IconTrash } from '@tabler/icons-react';

const iconStyle = { width: rem(16), height: rem(16) };

/**
 * Ordered list of steps; each step has an action and its expected result.
 */
export default function StepsEditor({ steps, onChange, errors = {}, readOnly = false }) {
  const update = (index, field, value) =>
    onChange(steps.map((step, i) => (i === index ? { ...step, [field]: value } : step)));

  const move = (index, offset) => {
    const target = index + offset;

    if (target < 0 || target >= steps.length) {
      return;
    }

    const reordered = [...steps];
    [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
    onChange(reordered);
  };

  const remove = index => onChange(steps.filter((_, i) => i !== index));

  const add = () => onChange([...steps, { action: '', expected_result: '' }]);

  return (
    <Stack gap='xs'>
      {steps.length === 0 && (
        <Text
          size='sm'
          c='dimmed'
        >
          No steps yet
        </Text>
      )}

      {steps.map((step, index) => (
        <Paper
          key={index}
          withBorder
          radius='md'
          p='sm'
        >
          <Group
            align='flex-start'
            wrap='nowrap'
            gap='sm'
          >
            <Text
              fw={700}
              c='dimmed'
              w={22}
              pt={6}
            >
              {index + 1}
            </Text>
            <Textarea
              style={{ flex: 1 }}
              placeholder='Step'
              autosize
              minRows={1}
              value={step.action}
              onChange={event => update(index, 'action', event.target.value)}
              error={errors[`steps.${index}.action`]}
              readOnly={readOnly}
            />
            <Textarea
              style={{ flex: 1 }}
              placeholder='Expected result'
              autosize
              minRows={1}
              value={step.expected_result ?? ''}
              onChange={event => update(index, 'expected_result', event.target.value)}
              readOnly={readOnly}
            />
            {!readOnly && (
              <Group
                gap={0}
                wrap='nowrap'
              >
                <Tooltip
                  label='Move up'
                  openDelay={500}
                >
                  <ActionIcon
                    variant='subtle'
                    color='gray'
                    disabled={index === 0}
                    onClick={() => move(index, -1)}
                  >
                    <IconArrowUp style={iconStyle} />
                  </ActionIcon>
                </Tooltip>
                <Tooltip
                  label='Move down'
                  openDelay={500}
                >
                  <ActionIcon
                    variant='subtle'
                    color='gray'
                    disabled={index === steps.length - 1}
                    onClick={() => move(index, 1)}
                  >
                    <IconArrowDown style={iconStyle} />
                  </ActionIcon>
                </Tooltip>
                <Tooltip
                  label='Remove step'
                  openDelay={500}
                >
                  <ActionIcon
                    variant='subtle'
                    color='red'
                    onClick={() => remove(index)}
                  >
                    <IconTrash style={iconStyle} />
                  </ActionIcon>
                </Tooltip>
              </Group>
            )}
          </Group>
        </Paper>
      ))}

      {!readOnly && (
        <Button
          variant='light'
          size='xs'
          leftSection={<IconPlus size={14} />}
          onClick={add}
          w='fit-content'
        >
          Add step
        </Button>
      )}
    </Stack>
  );
}
