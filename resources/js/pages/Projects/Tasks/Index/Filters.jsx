import useTaskFiltersStore from "@/hooks/store/useTaskFiltersStore";
import { taskStatusOptions } from "@/utils/enums";
import { usePage } from "@inertiajs/react";
import { Button, ColorSwatch, Stack, Text } from "@mantine/core";
import FilterButton from "./Filters/FilterButton";
import classes from "./Filters/css/FilterButton.module.css";

export default function Filters() {
  const { usersWithAccessToProject, labels } = usePage().props;

  const { filters, toggleArrayFilter, toggleObjectFilter, toggleValueFilter, prioritySort, sortHighToLow, sortLowToHigh, clearPrioritySort } =
    useTaskFiltersStore();

  return (
    <>
      <Stack justify="flex-start" gap={24}>
        <div>
          <Text fz="xs" fw={700} tt="uppercase" mb="sm">
            Priority
          </Text>
          <Button.Group>
            <Button
              className={classes.button}
              size="xs"
              variant={prioritySort === null ? "filled" : "default"}
              radius="md"
              onClick={clearPrioritySort}
            >
              Default
            </Button>
            <Button
              className={classes.button}
              size="xs"
              variant={prioritySort === "asc" ? "filled" : "default"}
              radius="md"
              onClick={sortHighToLow}
            >
              High
            </Button>
            <Button
              className={classes.button}
              size="xs"
              variant={prioritySort === "desc" ? "filled" : "default"}
              radius="md"
              onClick={sortLowToHigh}
            >
              Low
            </Button>
          </Button.Group>
        </div>

        {usersWithAccessToProject.length > 0 && (
          <div>
            <Text fz="xs" fw={700} tt="uppercase" mb="sm">
              Assignees
            </Text>
            <Stack justify="flex-start" gap={6}>
              {usersWithAccessToProject.map((item) => (
                <FilterButton
                  key={item.id}
                  selected={filters.assignees.includes(item.id)}
                  onClick={() => toggleArrayFilter("assignees", item.id)}
                >
                  {item.name}
                </FilterButton>
              ))}
            </Stack>
          </div>
        )}

        <div>
          <Text fz="xs" fw={700} tt="uppercase" mb="sm">
            Due date
          </Text>
          <Stack justify="flex-start" gap={6}>
            <FilterButton
              selected={filters.due_date.not_set === 1}
              onClick={() => toggleObjectFilter("due_date", "not_set")}
            >
              Not set
            </FilterButton>
            <FilterButton
              selected={filters.due_date.overdue === 1}
              onClick={() => toggleObjectFilter("due_date", "overdue")}
            >
              Overdue
            </FilterButton>
          </Stack>
        </div>

        {labels.length > 0 && (
          <div>
            <Text fz="xs" fw={700} tt="uppercase" mb="sm">
              Labels
            </Text>
            <Stack justify="flex-start" gap={6}>
              {labels.map((item) => (
                <FilterButton
                  key={item.id}
                  selected={filters.labels.includes(item.id)}
                  onClick={() => toggleArrayFilter("labels", item.id)}
                  leftSection={<ColorSwatch color={item.color} size={18} />}
                >
                  {item.name}
                </FilterButton>
              ))}
            </Stack>
          </div>
        )}

        <div>
          <Text fz="xs" fw={700} tt="uppercase" mb="sm">
            Completion
          </Text>
          <Stack justify="flex-start" gap={6}>
            <FilterButton
              selected={filters.status === "completed"}
              onClick={() => toggleValueFilter("status", "completed")}
            >
              Show completed tasks
            </FilterButton>
          </Stack>
        </div>

        <div>
          <Text fz="xs" fw={700} tt="uppercase" mb="sm">
            Status
          </Text>
          <Stack justify="flex-start" gap={6}>
            {taskStatusOptions.map((item) => (
              <FilterButton
                key={item.value}
                selected={filters.statuses.includes(item.value)}
                onClick={() => toggleArrayFilter("statuses", item.value)}
              >
                {item.label}
              </FilterButton>
            ))}
          </Stack>
        </div>
      </Stack>
    </>
  );
}
