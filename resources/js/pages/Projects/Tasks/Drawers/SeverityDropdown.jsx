import { severityOptions } from '@/utils/enums';
import { ColorSwatch, Group, Select, Text } from '@mantine/core';

export default function SeverityDropdown({ value, onChange, readOnly = false, ...props }) {
  const selectedSeverity = severityOptions.find(s => s.value === value);

  return (
    <Select
      label='Severity'
      placeholder='Select severity'
      clearable={!readOnly}
      readOnly={readOnly}
      value={value || null}
      onChange={val => onChange(val || null)}
      data={severityOptions.map(({ value, label }) => ({ value, label }))}
      leftSection={
        selectedSeverity ? (
          <ColorSwatch
            color={selectedSeverity.color}
            size={10}
          />
        ) : null
      }
      renderOption={({ option }) => (
        <Group gap={7}>
          <ColorSwatch
            color={severityOptions.find(s => s.value === option.value)?.color}
            size={10}
          />
          <Text size='sm'>{option.label}</Text>
        </Group>
      )}
      comboboxProps={{ withinPortal: false }}
      {...props}
    />
  );
}
