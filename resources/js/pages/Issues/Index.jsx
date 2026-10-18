import Pagination from '@/components/Pagination';
import Layout from '@/layouts/MainLayout';
import { router, usePage } from '@inertiajs/react';
import { Title } from '@mantine/core';
import IssueFilters from './IssueFilters';
import IssuesTable from './IssuesTable';

const AllIssuesIndex = () => {
  const { items, dropdowns, filterOptions } = usePage().props;

  return (
    <>
      <Title
        order={1}
        mb='xl'
      >
        Issues
      </Title>

      <IssueFilters
        dropdowns={dropdowns}
        assignees={filterOptions.assignees}
        reporters={filterOptions.reporters}
        labels={filterOptions.labels}
      />

      <IssuesTable
        issues={items.data}
        dropdowns={dropdowns}
        showProject
        onRowClick={issue =>
          router.get(route('projects.issues.open', [issue.project_id, issue.id]))
        }
      />

      {items.meta.last_page > 1 && (
        <Pagination
          current={items.meta.current_page}
          pages={items.meta.last_page}
        />
      )}
    </>
  );
};

AllIssuesIndex.layout = page => <Layout title='Issues'>{page}</Layout>;

export default AllIssuesIndex;
