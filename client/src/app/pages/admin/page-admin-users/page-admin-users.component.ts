import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatSort } from '@angular/material/sort';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { Options, Pagination, UserFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';

@Component({
  selector: 'cb-page-admin-users',
  templateUrl: './page-admin-users.component.html',
  styleUrls: ['./page-admin-users.component.scss'],
})
export class PageAdminUsersComponent implements OnInit, AfterViewInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  displayedColumns: string[] = ['id', 'email', 'name', 'emailVerified', 'isAdmin', 'lastUpdated', 'admin'];

  loading = false;
  error = false;

  users: Pagination<UserFull> | null = null;

  constructor(
    private api: ApiService,
    private user: UserService,
    private dialog: MatDialog,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadUsers();
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => {
      this.paginator.pageIndex = 0;
      this.loadUsers();
    });

    this.paginator.page.subscribe(() => {
      this.loadUsers();
    });
  }

  async loadUsers() {
    this.error = false;
    this.loading = true;

    let options: Options = {};

    if (this.paginator) {
      options.itemsPerPage = this.paginator.pageSize;
      options.page = this.paginator.pageIndex;
    }

    if (this.sort && this.sort.active) {
      options.sort = this.sort.active;
      options.sortDirection = this.sort.direction.match('asc') ? 'asc' : 'desc';
    } else {
      options.sort = 'id';
      options.sortDirection = 'asc';
    }

    let res = await this.api.admin.getUsers(options);

    if (res.isOK()) {
      this.users = res.value;
    } else {
      this.error = true;
      console.error('Error loading users:', res.error);
    }

    this.loading = false;
  }

  get tableData() {
    return this.users?.items || [];
  }

  isCurrentUser(id: number) {
    return this.user.user?.id === id;
  }

  private getUserIndex(id: number) {
    return this.tableData.findIndex((user) => user.id === id);
  }

  async showEditPasswordDialog(id: number) {
    // TODO: show edit-password-dialog
  }

  async resetPassword(id: number) {
    this.loading = true;

    let res = await this.api.admin.resetUserPassword(id);

    if (res.isOK()) {
      this.dialog.open(ConfirmDialogComponent, {
        data: {
          title: 'Password was reset',
          content: `The new password is: ${res.value?.password}`,
          btnConfirm: 'OK',
          onlyOk: true,
        },
      });

      await this.loadUsers();
    } else {
      this.snackBar.open('Error resetting password!', 'OK', {
        panelClass: 'action-warn',
      });
      console.error('Error resetting password:', res.error);
    }

    this.loading = false;
  }

  async deleteUser(id: number) {
    let index = this.getUserIndex(id);

    if (index === -1) return;

    let user = this.tableData[index];

    let doDelete = await this.dialog
      .open(ConfirmDialogComponent, {
        data: {
          title: 'Delete user?',
          content: `Are you sure you want to delete this user: "${user.email}"? This action is not reversible`,
          warn: true,
        },
      })
      .afterClosed()
      .toPromise();

    if (!doDelete) return;

    this.loading = true;

    let res = await this.api.admin.deleteUser(id);

    if (res.isOK()) {
      this.snackBar.open('User deleted successfully!', 'OK', {
        duration: 5000,
      });
      await this.loadUsers();
    } else {
      this.snackBar.open('Error deleting user!', 'OK', {
        panelClass: 'action-warn',
      });
      console.error('Error deleting user:', res.error);
    }

    this.loading = false;
  }
}
