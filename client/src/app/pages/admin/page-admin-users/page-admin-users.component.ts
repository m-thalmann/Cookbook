import { AfterViewInit, Component, OnDestroy, ViewChild } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { InputDialogComponent } from 'src/app/components/input-dialog/input-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions, Pagination, UserFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { SubSink } from 'src/app/core/functions';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { CreateUserDialogComponent } from './components/create-user-dialog/create-user-dialog.component';

@Component({
  selector: 'cb-page-admin-users',
  templateUrl: './page-admin-users.component.html',
  styleUrls: ['./page-admin-users.component.scss'],
})
export class PageAdminUsersComponent implements AfterViewInit, OnDestroy {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  displayedColumns: string[] = ['id', 'email', 'name', 'emailVerified', 'isAdmin', 'lastUpdated', 'admin'];

  loading = true;
  error = false;

  users: Pagination<UserFull> | null = null;

  search = '';

  editUserId: number | null = null;
  editUserValues = {
    email: new FormControl('', [Validators.email, Validators.required]),
    name: new FormControl('', Validators.required),
  };

  private _search: string | null = null;

  private subSink = new SubSink();

  constructor(
    private api: ApiService,
    private user: UserService,
    private dialog: MatDialog,
    private snackbar: SnackbarService
  ) {}

  setEditUser(user: UserFull | null) {
    if (user === null) {
      this.editUserId = null;
    } else {
      this.editUserId = user.id;
      this.editUserValues.email.setValue(user.email);
      this.editUserValues.name.setValue(user.name);
    }
  }

  get isEditUserValid() {
    return this.editUserId !== null && this.editUserValues.email.valid && this.editUserValues.name.valid;
  }

  ngAfterViewInit() {
    this.loadUsers();

    this.subSink.push(
      this.sort.sortChange.subscribe(() => {
        this.paginator.pageIndex = 0;
        this.loadUsers();
      })
    );

    this.subSink.push(
      this.paginator.page.subscribe(() => {
        this.loadUsers();
      })
    );
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

    let options: ApiOptions = {};

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
          translate: true,
          title: 'pages.admin.users.enter_new_password',
          label: 'user.new_password',
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
      this.snackbar.info('messages.admin.new_password_successfully_saved');

      await this.loadUsers();
    } else {
      this.snackbar.error('messages.admin.error_saving_new_password');
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
          translate: true,
          translationKey: 'dialogs.password_was_reset',
          contentReplace: {
            password: res.value?.password || '',
          },
          onlyOk: true,
        },
      });

      await this.loadUsers();
    } else {
      this.snackbar.error('messages.admin.error_resetting_password');
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
          translate: true,
          translationKey: 'dialogs.delete_user',
          contentReplace: { email: user.email },
          warn: true,
        },
      })
      .afterClosed()
      .toPromise();

    if (!doDelete) return;

    this.loading = true;

    let res = await this.api.admin.deleteUser(id);

    if (res.isOK()) {
      this.snackbar.info('messages.admin.user_deleted_successfully');
      await this.loadUsers();
    } else {
      this.snackbar.error('messages.admin.error_deleting_user');
      console.error('Error deleting user:', res.error);
    }

    this.loading = false;
  }

  async toggleAdmin(user: UserFull) {
    if (this.loading || this.isCurrentUser(user.id) || this.editUserId === user.id) {
      return;
    }

    let doEditAdmin = await this.dialog
      .open(ConfirmDialogComponent, {
        data: {
          translate: true,
          translationKey: user.isAdmin ? 'dialogs.revoke_admin' : 'dialogs.grant_admin',
          contentReplace: { email: user.email },
        },
      })
      .afterClosed()
      .toPromise();

    if (!doEditAdmin) return;

    this.loading = true;

    let res = await this.api.admin.updateUser(user.id, { isAdmin: !user.isAdmin });

    if (res.isOK()) {
      this.snackbar.info('messages.admin.user_updated_successfully');
      await this.loadUsers();
    } else {
      this.snackbar.error('messages.admin.error_updating_user');
      console.error('Error updating user:', res.error);
    }

    this.loading = false;
  }

  async toggleEmailVerified(user: UserFull) {
    if (this.loading || this.isCurrentUser(user.id) || this.editUserId === user.id) {
      return;
    }

    this.loading = true;

    let res = await this.api.admin.updateUser(user.id, { emailVerified: !user.emailVerified });

    if (res.isOK()) {
      this.snackbar.info('messages.admin.user_updated_successfully');
      await this.loadUsers();
    } else {
      this.snackbar.error('messages.admin.error_updating_user');
      console.error('Error updating user:', res.error);
    }

    this.loading = false;
  }

  async editUser() {
    let index = this.getUserIndex(this.editUserId || -1);
    if (index === -1) return;

    let user = this.tableData[index];

    let email: string | undefined = this.editUserValues.email.value;
    let name: string | undefined = this.editUserValues.name.value;

    if (email === user.email) {
      email = undefined;
    }
    if (name === user.name) {
      name = undefined;
    }

    if (email || name) {
      this.loading = true;

      let res = await this.api.admin.updateUser(user.id, { email: email, name: name });

      if (res.isOK()) {
        this.snackbar.info('messages.admin.user_updated_successfully');
        this.editUserId = null;
        await this.loadUsers();
      } else {
        this.snackbar.error('messages.admin.error_updating_user');
        console.error('Error updating user:', res.error);
      }

      this.loading = false;
    } else {
      this.editUserId = null;
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
