import { AfterViewInit, Component, ViewChild } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatSort } from '@angular/material/sort';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { InputDialogComponent } from 'src/app/components/input-dialog/input-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { Options, Pagination, UserFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { CreateUserDialogComponent } from './components/create-user-dialog/create-user-dialog.component';

@Component({
  selector: 'cb-page-admin-users',
  templateUrl: './page-admin-users.component.html',
  styleUrls: ['./page-admin-users.component.scss'],
})
export class PageAdminUsersComponent implements AfterViewInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  displayedColumns: string[] = ['id', 'email', 'name', 'emailVerified', 'isAdmin', 'lastUpdated', 'admin'];

  loading = true;
  error = false;

  users: Pagination<UserFull> | null = null;

  search = '';

  private _search: string | null = null;

  constructor(
    private api: ApiService,
    private user: UserService,
    private dialog: MatDialog,
    private snackBar: MatSnackBar
  ) {}

  ngAfterViewInit() {
    this.loadUsers();

    this.sort.sortChange.subscribe(() => {
      this.paginator.pageIndex = 0;
      this.loadUsers();
    });

    this.paginator.page.subscribe(() => {
      this.loadUsers();
    });
  }

  applySearch() {
    let search: string | null = this.search.trim();

    if (search.length === 0) {
      search = null;
    }

    if (search !== this._search) {
      this._search = search;
      this.loadUsers();
    }
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

    let res = await this.api.admin.getUsers(this._search, options);

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

  async editPassword(id: number) {
    this.loading = true;

    let newPassword = await this.dialog
      .open(InputDialogComponent, {
        data: {
          title: 'Enter new password',
          type: 'password',
        },
      })
      .afterClosed()
      .toPromise();

    if (!newPassword) {
      this.loading = false;
      return;
    }

    let res = await this.api.admin.updateUser(id, { password: newPassword });

    if (res.isOK()) {
      this.snackBar.open('New password set successfully', 'OK', {
        duration: 5000,
      });

      await this.loadUsers();
    } else {
      this.snackBar.open('Error setting new password!', 'OK', {
        panelClass: 'action-warn',
      });
      console.error('Error setting new password:', res.error);
    }

    this.loading = false;
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

  async createUser() {
    let saved = await this.dialog.open(CreateUserDialogComponent).afterClosed().toPromise();

    if (saved) {
      this.loadUsers();
    }
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
