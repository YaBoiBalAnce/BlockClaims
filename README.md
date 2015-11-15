# BlockClaims
Simple Area Claiming system for PocketMine

STATUS: Working

| Commands For Players      | Permission | Description |
| ------------- | ------------- | ------------- |
| /claim addbuilder [playername] | blockclaims.addbuilder | add player to build in your claim |
| /claim removebuilder  | blockclaims.removebuilder  | removes player from building in you claim |
| /claim listbuilders | blockclaims.listbuilders | list players that are able to build in your claim |
| /claim resetclaims | none | resets player who executed command claims |


|Commands for Admins| Permission | Description |
| ------------- | ------------- | ------------- |
| /claim resetclaims [player] | blockclaims.resetclaims.other | resets player who executed command claims |
| NoCommand | blockclaims.override | break/place blocks in claimed areas |
| NoCommand | blockclaims.overclaim | bypasses claim limit |

How to use:
- Place ClaimBlock(default: spongeblock) it will claim area (size specified in config) ClaimBlock is center
- Area will be claimed for that player
- you can delete your own claims by breaking your claimblock or by /claim resetclaims(Note: Command will reset all you claims)

ToDo:
- ~~add support for multiple claims for player~~ DONE
- ~~add permission system~~ DONE
- ~~add config to change block-type, claim size, etc~~ DONE
- ~~add commands to modify claims (delete, allow other players to build, etc)~~ DONE
